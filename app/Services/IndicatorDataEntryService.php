<?php

namespace App\Services;

use App\Models\IndicatorDataEntry;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class IndicatorDataEntryService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $expenses
     * @param  list<UploadedFile>  $evidenceFiles
     */
    public function create(array $data, array $rows, array $expenses, array $evidenceFiles, User $user, array $activities = []): IndicatorDataEntry
    {
        $this->authorizeAssignedIndicator($user, (int) $data['indicator_id']);

        return DB::transaction(function () use ($data, $rows, $expenses, $evidenceFiles, $user, $activities): IndicatorDataEntry {
            $entry = IndicatorDataEntry::create($data + [
                'entered_by' => $user->id,
                'status' => 'draft',
            ]);

            $this->syncRows($entry, $rows);
            $this->syncExpenses($entry, $expenses);
            $this->syncActivities($entry, $activities);
            $this->attachEvidence($entry, $evidenceFiles);

            return $entry;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>|null  $rows
     * @param  list<array<string, mixed>>|null  $expenses
     * @param  list<UploadedFile>  $evidenceFiles
     */
    public function update(IndicatorDataEntry $entry, array $data, ?array $rows, ?array $expenses, array $evidenceFiles, User $user, ?array $activities = null): IndicatorDataEntry
    {
        $this->authorizeAssignedIndicator($user, (int) ($data['indicator_id'] ?? $entry->indicator_id));

        if (! in_array($entry->status, ['draft', 'rejected'], true)) {
            throw new AuthorizationException('Only draft or rejected entries can be edited.');
        }

        DB::transaction(function () use ($entry, $data, $rows, $expenses, $evidenceFiles, $activities): void {
            $entry->update($data);

            if ($rows !== null) {
                $this->syncRows($entry, $rows);
            }

            if ($expenses !== null) {
                $this->syncExpenses($entry, $expenses);
            }

            if ($activities !== null) {
                $this->syncActivities($entry, $activities);
            }

            $this->attachEvidence($entry, $evidenceFiles);
        });

        return $entry->fresh();
    }

    private function authorizeAssignedIndicator(User $user, int $indicatorId): void
    {
        if (! $user->can('indicator.view-all') && ! in_array($indicatorId, $user->assignedIndicatorIds(), true)) {
            throw new AuthorizationException('This indicator is not assigned to you.');
        }
    }

    public function removeEvidence(IndicatorDataEntry $entry, Media $media): void
    {
        abort_unless($media->model_type === IndicatorDataEntry::class && $media->model_id === $entry->id, 404);

        if (! in_array($entry->status, ['draft', 'rejected'], true)) {
            throw new AuthorizationException('Only draft or rejected entries can have evidence removed.');
        }

        $media->delete();
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function syncRows(IndicatorDataEntry $entry, array $rows): void
    {
        $entry->rows()->delete();

        foreach ($rows as $rowData) {
            $row = $entry->rows()->create([
                'label' => $rowData['label'] ?? null,
                'value' => $rowData['value'],
            ]);

            if (! empty($rowData['dimension_option_ids'])) {
                $row->dimensionOptions()->sync($rowData['dimension_option_ids']);
            }
        }
    }

    /** @param  list<array<string, mixed>>  $expenses */
    private function syncExpenses(IndicatorDataEntry $entry, array $expenses): void
    {
        $entry->expenses()->delete();

        foreach ($expenses as $expenseData) {
            $entry->expenses()->create($expenseData);
        }
    }

    /** @param list<array<string, mixed>> $activities */
    private function syncActivities(IndicatorDataEntry $entry, array $activities): void
    {
        $entry->activities()->delete();
        foreach ($activities as $activity) {
            $entry->activities()->create($activity);
        }
    }

    /** @param  list<UploadedFile>  $evidenceFiles */
    private function attachEvidence(IndicatorDataEntry $entry, array $evidenceFiles): void
    {
        foreach ($evidenceFiles as $file) {
            $entry->addMedia($file)->toMediaCollection('evidence');
        }
    }
}
