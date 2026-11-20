<?php

namespace App\Services;

use App\Models\IndicatorApprovalAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\User;
use App\Support\AdminLocationLevel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use App\Services\IndicatorPlanService;
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

        $this->assertNumericActualValuePresent($entry);
        $this->assertReconciliation($entry);
        if ($entry->domestic_spent !== null || $entry->external_spent !== null) {
            $reported = (float) ($entry->domestic_spent ?? 0) + (float) ($entry->external_spent ?? 0);
            if ($entry->budget_used !== null && abs($reported - (float) $entry->budget_used) > 0.01) {
                throw ValidationException::withMessages(['budget_used' => 'Domestic and external expenditure must total the reported budget used.']);
            }
        }

        if ($entry->indicator->requires_hierarchical_approval && $entry->location_level && $entry->location_id) {
            $chain = AdminLocationLevel::ancestorChain($entry->location_level, (int) $entry->location_id);
            $sequence = 1;
            $entry->approvalSteps()->delete();
            foreach (['ward', 'council', 'district', 'region'] as $level) {
                if (isset($chain[$level])) {
                    $entry->approvalSteps()->create([
                        'location_level' => $level,
                        'location_id' => $chain[$level]['id'],
                        'sequence' => $sequence++,
                        'status' => 'pending',
                    ]);
                }
            }
        }

        $entry->update([
            'institution_confirmed_by' => null, 'institution_confirmed_at' => null,
            'status' => 'pending_approval',
            'submitted_at' => now(),
        ]);

        return $entry->fresh();
    }

    public function approve(IndicatorDataEntry $entry, User $actor, ?string $comment): IndicatorDataEntry
    {
        if (! in_array($entry->status, ['submitted', 'pending_approval'], true)) {
            throw ValidationException::withMessages(['status' => 'Only submitted or pending entries can be approved.']);
        }
        $this->assertReviewerScope($entry, $actor);
        if (! $entry->institution_confirmed_at) {
            throw ValidationException::withMessages(['status' => 'The reporting institution must confirm this submission first.']);
        }

        $currentStep = $entry->approvalSteps()->where('status', 'pending')->orderBy('sequence')->first();
        if ($currentStep) {
            $assigned = IndicatorApprovalAssignment::query()->where('user_id', $actor->id)->where('is_active', true)
                ->where('location_level', $currentStep->location_level)->where('location_id', $currentStep->location_id)->exists();
            if (! $actor->hasRole('Super Admin') && ! $assigned) {
                throw new AuthorizationException('This approval step is assigned to another geographic approver.');
            }
            $currentStep->update(['status' => 'approved', 'approved_by' => $actor->id, 'decided_at' => now(), 'comment' => $comment]);
            // Finishing the geographic chain does not bypass thematic sign-off.
            $entry->update(['status' => 'pending_approval']);
            return $entry->fresh();
        }

        if (! $actor->hasRole('Super Admin') && ! $actor->hasRole('Thematic Manager')) {
            throw new AuthorizationException('Final approval is reserved for the thematic area coordinator.');
        }
        if ($entry->entered_by === $actor->id && ! $actor->hasRole('Super Admin')) {
            throw new AuthorizationException('You cannot give final approval to your own submission.');
        }
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
        if (! in_array($entry->status, ['submitted', 'pending_approval'], true)) {
            throw ValidationException::withMessages(['status' => 'Only submitted or pending entries can be returned.']);
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

    public function confirmInstitution(IndicatorDataEntry $entry, User $actor): IndicatorDataEntry
    {
        if (! in_array($entry->status, ['submitted','pending_approval'], true)) {
            throw ValidationException::withMessages(['status' => 'Only submitted entries may be confirmed.']);
        }
        $organizationId = $entry->organization_id ?: $entry->plan?->lead_organization_id;
        if (! $organizationId) {
            throw ValidationException::withMessages(['organization_id' => 'A reporting institution is required.']);
        }
        app(IndicatorPlanService::class)->authorizeInstitution($actor, (int) $organizationId, (int) $entry->entered_by);
        if ($entry->institution_confirmed_at) {
            throw ValidationException::withMessages(['status' => 'This entry has already been confirmed.']);
        }
        $entry->update(['institution_confirmed_by' => $actor->id, 'institution_confirmed_at' => now()]);
        $entry->reviews()->create(['reviewed_by' => $actor->id, 'action' => 'confirmed', 'comment' => 'Institutional ownership confirmed']);
        return $entry->fresh();
    }

    /** Institutional officers may return unconfirmed reports to the original collector. */
    public function returnFromInstitution(IndicatorDataEntry $entry, User $actor, string $reason): IndicatorDataEntry
    {
        if (! in_array($entry->status, ['submitted','pending_approval'], true) || $entry->institution_confirmed_at) {
            throw ValidationException::withMessages(['status' => 'Only unconfirmed submissions may be returned by institutions.']);
        }
        if (blank($reason)) {
            throw ValidationException::withMessages(['comment' => 'State the reason for returning this submission.']);
        }
        $organizationId = $entry->organization_id ?: $entry->plan?->lead_organization_id;
        app(IndicatorPlanService::class)->authorizeInstitution($actor, (int) $organizationId, (int) $entry->entered_by);
        return DB::transaction(function () use ($entry, $actor, $reason): IndicatorDataEntry {
            $entry->approvalSteps()->delete();
            $entry->update(['status' => 'rejected', 'institution_confirmed_by' => null, 'institution_confirmed_at' => null]);
            $entry->reviews()->create(['reviewed_by' => $actor->id, 'action' => 'rejected', 'comment' => $reason]);
            return $entry->fresh();
        });
    }

    private function assertNumericActualValuePresent(IndicatorDataEntry $entry): void
    {
        $type = $entry->indicator->measurementType?->code;

        if (in_array($type, ['text', 'qualitative'], true)) {
            return;
        }

        if ($entry->actual_value === null) {
            throw ValidationException::withMessages([
                'actual_value' => __('Enter a number starting from 0. This field cannot be left empty.'),
            ]);
        }
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
        $assignedAreaIds = $actor->visibleThematicAreaIds();
        if (! in_array($thematicAreaId, $assignedAreaIds, true)) {
            throw new AuthorizationException('You may only review entries in your assigned thematic areas.');
        }
    }
}
