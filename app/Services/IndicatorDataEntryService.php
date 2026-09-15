<?php

namespace App\Services;

use App\Models\IndicatorDataAssignment;
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
    public function create(array $data, array $rows, array $expenses, array $evidenceFiles, User $user): IndicatorDataEntry
    {
        $this->assertAssignmentScope(
            $user,
            (int) $data['indicator_id'],
            $data['location_level'] ?? null,
            isset($data['location_id']) ? (int) $data['location_id'] : null,
            isset($data['organization_id']) ? (int) $data['organization_id'] : null,
        );

        return DB::transaction(function () use ($data, $rows, $expenses, $evidenceFiles, $user): IndicatorDataEntry {
            $entry = IndicatorDataEntry::create($data + [
                'entered_by' => $user->id,
                'status' => 'draft',
            ]);

            $this->syncRows($entry, $rows);
            $this->syncExpenses($entry, $expenses);
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
    public function update(IndicatorDataEntry $entry, array $data, ?array $rows, ?array $expenses, array $evidenceFiles, User $user): IndicatorDataEntry
    {
        if (! in_array($entry->status, ['draft', 'rejected'], true)) {
            throw new AuthorizationException('Only draft or rejected entries can be edited.');
        }

        $indicatorId = (int) ($data['indicator_id'] ?? $entry->indicator_id);
        $locationLevel = array_key_exists('location_level', $data) ? $data['location_level'] : $entry->location_level;
        $locationId = array_key_exists('location_id', $data) ? $data['location_id'] : $entry->location_id;
        $organizationId = array_key_exists('organization_id', $data) ? $data['organization_id'] : $entry->organization_id;

        $this->assertAssignmentScope(
            $user,
            $indicatorId,
            $locationLevel,
            $locationId !== null ? (int) $locationId : null,
            $organizationId !== null ? (int) $organizationId : null,
        );

        DB::transaction(function () use ($entry, $data, $rows, $expenses, $evidenceFiles): void {
            $entry->update($data);

            if ($rows !== null) {
                $this->syncRows($entry, $rows);
            }

            if ($expenses !== null) {
                $this->syncExpenses($entry, $expenses);
            }

            $this->attachEvidence($entry, $evidenceFiles);
        });

        return $entry->fresh();
    }

    public function removeEvidence(IndicatorDataEntry $entry, Media $media): void
    {
        abort_unless($media->model_type === IndicatorDataEntry::class && $media->model_id === $entry->id, 404);

        if (! in_array($entry->status, ['draft', 'rejected'], true)) {
            throw new AuthorizationException('Only draft or rejected entries can have evidence removed.');
        }

        $media->delete();
    }

    private function assertAssignmentScope(
        User $user,
        int $indicatorId,
        ?string $locationLevel,
        ?int $locationId,
        ?int $organizationId,
    ): void
    {
        if ($user->hasRole('Super Admin')) {
            return;
        }

        $assignments = IndicatorDataAssignment::query()
            ->where('indicator_id', $indicatorId)
            ->where('is_active', true)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id);

                if ($user->organization_id !== null) {
                    $query->orWhere('organization_id', $user->organization_id);
                }
            })
            ->get();

        if ($assignments->isEmpty()) {
            throw new AuthorizationException('You are not assigned to report on this indicator.');
        }

        $matches = $assignments->contains(
            fn (IndicatorDataAssignment $assignment) => ($assignment->location_level === null
                    || ($assignment->location_level === $locationLevel && (int) $assignment->location_id === $locationId))
                && ($assignment->organization_id === null || (int) $assignment->organization_id === $organizationId)
        );

        if (! $matches) {
            throw new AuthorizationException('You are not assigned to report on this indicator for the given location and organization.');
        }
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

    /** @param  list<UploadedFile>  $evidenceFiles */
    private function attachEvidence(IndicatorDataEntry $entry, array $evidenceFiles): void
    {
        foreach ($evidenceFiles as $file) {
            $entry->addMedia($file)->toMediaCollection('evidence');
        }
    }
}
