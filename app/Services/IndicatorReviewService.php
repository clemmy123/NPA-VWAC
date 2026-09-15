<?php

namespace App\Services;

use App\Models\IndicatorDataEntry;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IndicatorReviewService
{
    public function submit(IndicatorDataEntry $entry, User $actor): IndicatorDataEntry
    {
        if (! in_array($entry->status, ['draft', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => 'Only draft or rejected entries can be submitted.']);
        }

        if ($entry->entered_by !== $actor->id && ! $actor->hasRole('Super Admin')) {
            throw new AuthorizationException('You can only submit your own entries.');
        }

        $this->assertReconciliation($entry);

        $entry->update(['status' => 'submitted', 'submitted_at' => now()]);

        return $entry->fresh();
    }

    public function approve(IndicatorDataEntry $entry, User $actor, ?string $comment): IndicatorDataEntry
    {
        if ($entry->status !== 'submitted') {
            throw ValidationException::withMessages(['status' => 'Only submitted entries can be approved.']);
        }
        $this->assertReviewerScope($entry, $actor);

        DB::transaction(function () use ($entry, $actor, $comment): void {
            $entry->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $actor->id,
            ]);

            $entry->reviews()->create([
                'reviewed_by' => $actor->id,
                'action' => 'approved',
                'comment' => $comment,
            ]);
        });

        return $entry->fresh();
    }

    public function returnToSubmitter(IndicatorDataEntry $entry, User $actor, ?string $comment): IndicatorDataEntry
    {
        if ($entry->status !== 'submitted') {
            throw ValidationException::withMessages(['status' => 'Only submitted entries can be returned.']);
        }
        $this->assertReviewerScope($entry, $actor);

        DB::transaction(function () use ($entry, $actor, $comment): void {
            $entry->update(['status' => 'rejected']);

            $entry->reviews()->create([
                'reviewed_by' => $actor->id,
                'action' => 'rejected',
                'comment' => $comment,
            ]);
        });

        return $entry->fresh();
    }

    private function assertReconciliation(IndicatorDataEntry $entry): void
    {
        $requiresReconciliation = $entry->indicator->dimensions()->wherePivot('must_reconcile', true)->exists();

        if (! $requiresReconciliation) {
            return;
        }

        $rowsTotal = (float) $entry->rows()->sum('value');
        $actual = (float) ($entry->actual_value ?? 0);

        if (abs($rowsTotal - $actual) > 0.0001) {
            throw ValidationException::withMessages([
                'actual_value' => 'Disaggregated rows must sum to the entry total when reconciliation is required for this indicator.',
            ]);
        }
    }

    private function assertReviewerScope(IndicatorDataEntry $entry, User $actor): void
    {
        if ($actor->hasRole('Super Admin')) {
            return;
        }

        $thematicAreaId = (int) $entry->indicator()->value('thematic_area_id');
        $assignedAreaIds = $actor->assignedThematicAreaIds();
        if ($assignedAreaIds !== [] && ! in_array($thematicAreaId, $assignedAreaIds, true)) {
            throw new AuthorizationException('You may only review entries in your assigned thematic areas.');
        }
    }
}
