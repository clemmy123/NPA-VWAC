<?php

namespace Database\Seeders;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use App\Models\MeasurementType;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Project;
use App\Models\Region;
use App\Models\ReportingPeriod;
use App\Models\ThematicArea;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoReportDataSeeder extends Seeder
{
    public const string DEMO_REMARK = 'demo-report-seed';

    public function run(): void
    {
        $this->call([
            ConstantDataSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $user = User::query()->first() ?? User::factory()->create([
            'name' => 'Kenny Johnson',
            'email' => 'kenny@npa-vwac.test',
        ]);

        if (! $user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }

        $financialYear = FinancialYear::query()->where('is_current', true)->first()
            ?? FinancialYear::query()->orderByDesc('start_date')->firstOrFail();

        $reportingPeriod = ReportingPeriod::query()
            ->where('financial_year_id', $financialYear->id)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        $project = Project::query()->updateOrCreate(
            ['code' => 'NPA-VWAC'],
            [
                'name' => 'NPA-VAWC',
                'description' => 'A comprehensive program targeting leadership training, mentorship, and empowerment of women in rural and urban communities.',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'status' => 'active',
                'created_by' => $user->id,
            ],
        );

        $thematicArea = ThematicArea::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'name' => 'Household Economic Strengthening',
            ],
            [
                'description' => 'Increase women leadership and participation in local communities',
                'status' => 'active',
                'created_by' => $user->id,
            ],
        );

        $organizations = $this->organizations();
        $indicators = $this->indicators($thematicArea, $user);

        IndicatorTarget::query()->where('remarks', self::DEMO_REMARK)->delete();
        IndicatorDataEntry::withTrashed()->where('remarks', self::DEMO_REMARK)->forceDelete();

        foreach ($indicators as $row) {
            IndicatorTarget::query()->create([
                'indicator_id' => $row['indicator']->id,
                'financial_year_id' => $financialYear->id,
                'reporting_period_id' => null,
                'target_value' => $row['target'],
                'remarks' => self::DEMO_REMARK,
            ]);

            foreach ($row['actuals'] as $orgCode => $actual) {
                IndicatorDataEntry::factory()->approved()->create([
                    'indicator_id' => $row['indicator']->id,
                    'financial_year_id' => $financialYear->id,
                    'reporting_period_id' => $reportingPeriod?->id,
                    'organization_id' => $organizations[$orgCode]->id,
                    'entry_date' => now()->toDateString(),
                    'activity_name' => $row['indicator']->code.' · '.$orgCode,
                    'actual_value' => $actual,
                    'entered_by' => $user->id,
                    'approved_by' => $user->id,
                    'remarks' => self::DEMO_REMARK,
                ]);
            }
        }

        $this->seedRegionalActuals($indicators[0], $financialYear, $reportingPeriod, $user);
    }

    /**
     * @return array<string, Organization>
     */
    private function organizations(): array
    {
        $bankType = OrganizationType::query()->where('code', 'bank')->firstOrFail();
        $ngoType = OrganizationType::query()->where('code', 'ngo')->firstOrFail();

        return [
            'CRDB' => Organization::query()->firstOrCreate(
                ['code' => 'CRDB'],
                ['organization_type_id' => $bankType->id, 'name' => 'CRDB Bank', 'is_active' => true],
            ),
            'NMB' => Organization::query()->firstOrCreate(
                ['code' => 'NMB'],
                ['organization_type_id' => $bankType->id, 'name' => 'NMB Bank', 'is_active' => true],
            ),
            'TAWLA' => Organization::query()->firstOrCreate(
                ['code' => 'TAWLA'],
                ['organization_type_id' => $ngoType->id, 'name' => 'TAWLA', 'is_active' => true],
            ),
        ];
    }

    /**
     * @return list<array{indicator: Indicator, target: float, actuals: array<string, float>}>
     */
    private function indicators(ThematicArea $thematicArea, User $user): array
    {
        $percentage = MeasurementType::query()->where('code', 'percentage')->firstOrFail();
        $count = MeasurementType::query()->where('code', 'count')->firstOrFail();
        $percent = UnitOfMeasure::query()->where('code', 'percent')->firstOrFail();
        $women = UnitOfMeasure::query()->where('code', 'women')->firstOrFail();
        $organizations = UnitOfMeasure::query()->where('code', 'organizations')->firstOrFail();
        $households = UnitOfMeasure::query()->where('code', 'households')->firstOrFail();

        $definitions = [
            [
                'code' => 'HES-01',
                'name' => 'Percentage of Households below the National Basic Needs Poverty Line',
                'measurement_type_id' => $percentage->id,
                'unit_of_measure_id' => $households->id,
                'aggregation_method' => 'average',
                'target' => 25,
                'actuals' => ['CRDB' => 18, 'NMB' => 22, 'TAWLA' => 30],
            ],
            [
                'code' => 'HES-02',
                'name' => 'Percentage of Households experiencing Moderate or Severe Food Insecurity',
                'measurement_type_id' => $percentage->id,
                'unit_of_measure_id' => $percent->id,
                'aggregation_method' => 'average',
                'target' => 40,
                'actuals' => ['CRDB' => 45, 'NMB' => 50, 'TAWLA' => 35],
            ],
            [
                'code' => 'HES-03',
                'name' => 'Number of Women-owned SMEs',
                'measurement_type_id' => $count->id,
                'unit_of_measure_id' => $organizations->id,
                'aggregation_method' => 'sum',
                'target' => 500,
                'actuals' => ['CRDB' => 120, 'NMB' => 80, 'TAWLA' => 40],
            ],
            [
                'code' => 'HES-04',
                'name' => 'Number of Women participating in WEEPs',
                'measurement_type_id' => $count->id,
                'unit_of_measure_id' => $women->id,
                'aggregation_method' => 'sum',
                'target' => 1000,
                'actuals' => ['CRDB' => 400, 'NMB' => 350, 'TAWLA' => 250],
            ],
            [
                'code' => 'HES-05',
                'name' => 'Number of Women with access to Vocational Training',
                'measurement_type_id' => $count->id,
                'unit_of_measure_id' => $women->id,
                'aggregation_method' => 'sum',
                'target' => 800,
                'actuals' => ['CRDB' => 300, 'NMB' => 200, 'TAWLA' => 50],
            ],
            [
                'code' => 'HES-06',
                'name' => 'Proportion of Women aged 15–49 years who make their own Informed Decisions regarding Sexual relations, Contraceptive use and Reproductive Health Care',
                'measurement_type_id' => $percentage->id,
                'unit_of_measure_id' => $percent->id,
                'aggregation_method' => 'average',
                'target' => 70,
                'actuals' => [],
            ],
        ];

        $rows = [];

        foreach ($definitions as $definition) {
            $indicator = Indicator::query()->updateOrCreate(
                [
                    'thematic_area_id' => $thematicArea->id,
                    'code' => $definition['code'],
                ],
                [
                    'name' => $definition['name'],
                    'measurement_type_id' => $definition['measurement_type_id'],
                    'unit_of_measure_id' => $definition['unit_of_measure_id'],
                    'collection_mode' => 'progressive',
                    'aggregation_method' => $definition['aggregation_method'],
                    'reporting_frequency' => 'quarterly',
                    'collection_scope' => 'national',
                    'status' => 'active',
                    'created_by' => $user->id,
                ],
            );

            $rows[] = [
                'indicator' => $indicator,
                'target' => (float) $definition['target'],
                'actuals' => $definition['actuals'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array{indicator: Indicator, target: float, actuals: array<string, float>}  $row
     */
    private function seedRegionalActuals(array $row, FinancialYear $financialYear, ?ReportingPeriod $reportingPeriod, User $user): void
    {
        $reachedByRegion = [
            'Dodoma' => 30.0,
            'Arusha' => 62.0,
            'Kagera' => 74.0,
            'Kilimanjaro' => 90.0,
            'Manyara' => 94.0,
        ];

        foreach ($reachedByRegion as $name => $percent) {
            $region = Region::query()->firstOrCreate(
                ['name' => $name],
                ['code' => strtoupper(substr($name, 0, 3))],
            );

            IndicatorDataEntry::factory()->approved()->create([
                'indicator_id' => $row['indicator']->id,
                'financial_year_id' => $financialYear->id,
                'reporting_period_id' => $reportingPeriod?->id,
                'organization_id' => null,
                'location_level' => 'region',
                'location_id' => $region->region_id,
                'entry_date' => now()->toDateString(),
                'activity_name' => $row['indicator']->code.' · '.$name,
                'actual_value' => round($row['target'] * ($percent / 100), 4),
                'entered_by' => $user->id,
                'approved_by' => $user->id,
                'remarks' => self::DEMO_REMARK,
            ]);
        }
    }
}
