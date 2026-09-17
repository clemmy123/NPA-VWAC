<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Models\MeasurementType;
use App\Models\Project;
use App\Models\ThematicArea;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds the real NPA-VAWC plan hierarchy — one Project, its Thematic Areas,
 * and their Indicators — from the source planning spreadsheet
 * ("Indicators.for improving dashboard.Master.xls"). The spreadsheet itself
 * has no machine-readable thematic-area titles (its tabs are just
 * "Thematic.1".."Thematic.8", with "3" split into two tables), so the names
 * below were confirmed with the client rather than parsed; everything else
 * (indicator name/description, ordering) comes straight from
 * database/seeders/data/npa_vawc_indicators.json, extracted from the sheet
 * with xlrd. Re-running this seeder is safe: Project/ThematicArea/Indicator
 * are all matched on their natural key and updated in place.
 */
class NpaVawcPlanSeeder extends Seeder
{
    /** @var array<string, string> spreadsheet tab => short code used to build indicator codes */
    private const SHEET_CODES = [
        'Thematic.1' => 'T1',
        'Thematic.2' => 'T2',
        'Thematic.3.a' => 'T3A',
        'Thematic.3.b' => 'T3B',
        'Thematic.4' => 'T4',
        'Thematic.5' => 'T5',
        'Thematic.6' => 'T6',
        'Thematic.7' => 'T7',
        'Thematic.8' => 'T8',
    ];

    public function run(): void
    {
        $project = Project::query()->updateOrCreate(
            ['code' => 'NPA-VAWC'],
            [
                'name' => 'NPA-VAWC (2026-2030)',
                'description' => 'National Plan of Action to End Violence Against Women and Children.',
                'start_date' => '2026-07-01',
                'end_date' => '2031-06-30',
                'status' => 'active',
            ],
        );

        $rows = collect(json_decode(
            file_get_contents(__DIR__.'/data/npa_vawc_indicators.json'),
            true,
        ));

        $rows->groupBy('thematic_area')->each(function (Collection $indicatorRows, string $thematicAreaName) use ($project): void {
            $thematicArea = ThematicArea::query()->updateOrCreate(
                ['project_id' => $project->id, 'name' => $thematicAreaName],
                ['status' => 'active'],
            );

            foreach ($indicatorRows as $row) {
                $this->seedIndicator($thematicArea, $row);
            }
        });
    }

    /** @param  array{sheet: string, seq: int, name: string, description: string}  $row */
    private function seedIndicator(ThematicArea $thematicArea, array $row): void
    {
        $code = self::SHEET_CODES[$row['sheet']].'-'.str_pad((string) $row['seq'], 2, '0', STR_PAD_LEFT);

        [$measurementTypeCode, $unitCode] = $this->guessMeasurement($row['name']);
        $searchableText = strtolower($row['name'].' '.$row['description']);
        $hasBudgetImplication = $measurementTypeCode === 'currency'
            || str_contains($searchableText, 'budget allocated')
            || str_contains($searchableText, 'expenditure');
        $requiresActivity = collect(['conducted', 'training', 'trained', 'meeting', 'dialogue', 'campaign', 'programme', 'program'])
            ->contains(fn (string $keyword): bool => str_contains($searchableText, $keyword));

        Indicator::query()->updateOrCreate(
            ['thematic_area_id' => $thematicArea->id, 'code' => $code],
            [
                'name' => $row['name'],
                'description' => $row['description'] !== '' ? $row['description'] : null,
                'measurement_type_id' => MeasurementType::query()->where('code', $measurementTypeCode)->value('id'),
                'unit_of_measure_id' => UnitOfMeasure::query()->where('code', $unitCode)->value('id'),
                'collection_mode' => 'progressive',
                'aggregation_method' => match ($measurementTypeCode) {
                    'percentage', 'ratio' => 'average',
                    'yes_no' => 'latest',
                    default => 'sum',
                },
                'reporting_frequency' => 'quarterly',
                'collection_scope' => 'national',
                'requires_location' => false,
                'reporting_location_level' => null,
                'requires_activity' => $requiresActivity,
                'has_budget_implication' => $hasBudgetImplication,
                'requires_evidence' => false,
                'requires_hierarchical_approval' => false,
                'status' => 'active',
            ],
        );
    }

    /**
     * The spreadsheet has no measurement-type/unit columns, so this infers a
     * reasonable default from the indicator's wording. Anything that doesn't
     * match a keyword falls back to a plain headcount ('count' / 'people') —
     * good enough as a starting point; Thematic Managers can correct
     * individual indicators from the UI afterwards.
     *
     * @return array{0: string, 1: string} [measurement_type code, unit_of_measure code]
     */
    private function guessMeasurement(string $name): array
    {
        $lower = strtolower($name);

        if (str_contains($lower, 'amount') || str_contains($lower, 'budget')) {
            return ['currency', 'tzs'];
        }

        if (str_contains($lower, 'percentage') || str_contains($lower, 'proportion') || str_contains($lower, '%')) {
            return ['percentage', 'percent'];
        }

        if (str_contains($lower, 'in place')) {
            return ['yes_no', 'responses'];
        }

        $unit = match (true) {
            str_contains($lower, 'women') => 'women',
            str_contains($lower, 'child') || str_contains($lower, 'children') => 'children',
            str_contains($lower, 'household') => 'households',
            str_contains($lower, 'group') => 'groups',
            str_contains($lower, 'organi') => 'organizations',
            str_contains($lower, 'account') => 'accounts',
            str_contains($lower, 'case') => 'cases',
            default => 'people',
        };

        return ['count', $unit];
    }
}
