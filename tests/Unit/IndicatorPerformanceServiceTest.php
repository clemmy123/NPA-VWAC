<?php

namespace Tests\Unit;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use App\Models\Organization;
use App\Models\ReportingPeriod;
use App\Models\ThematicArea;
use App\Services\IndicatorPerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorPerformanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private IndicatorPerformanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new IndicatorPerformanceService;
    }

    public function test_sum_aggregation_adds_up_approved_entries_only(): void
    {
        $indicator = Indicator::factory()->create(['aggregation_method' => 'sum']);
        $financialYear = FinancialYear::factory()->create();
        IndicatorTarget::factory()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'target_value' => 1000]);

        IndicatorDataEntry::factory()->approved()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'actual_value' => 300]);
        IndicatorDataEntry::factory()->approved()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'actual_value' => 200]);
        IndicatorDataEntry::factory()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'actual_value' => 5000, 'status' => 'draft']);

        $result = $this->service->summarize($indicator, $financialYear);

        $this->assertSame(1000.0, $result['target_value']);
        $this->assertSame(500.0, $result['actual_value']);
        $this->assertSame(50.0, $result['achievement_percent']);
    }

    public function test_average_aggregation_averages_approved_entries(): void
    {
        $indicator = Indicator::factory()->create(['aggregation_method' => 'average']);
        $financialYear = FinancialYear::factory()->create();

        IndicatorDataEntry::factory()->approved()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'actual_value' => 10]);
        IndicatorDataEntry::factory()->approved()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'actual_value' => 20]);

        $result = $this->service->summarize($indicator, $financialYear);

        $this->assertSame(15.0, $result['actual_value']);
    }

    public function test_latest_aggregation_picks_the_most_recent_entry_date(): void
    {
        $indicator = Indicator::factory()->create(['aggregation_method' => 'latest']);
        $financialYear = FinancialYear::factory()->create();

        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id,
            'actual_value' => 10, 'entry_date' => now()->subDays(10)->toDateString(),
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id,
            'actual_value' => 99, 'entry_date' => now()->toDateString(),
        ]);

        $result = $this->service->summarize($indicator, $financialYear);

        $this->assertSame(99.0, $result['actual_value']);
    }

    public function test_count_aggregation_counts_approved_entries(): void
    {
        $indicator = Indicator::factory()->create(['aggregation_method' => 'count']);
        $financialYear = FinancialYear::factory()->create();

        IndicatorDataEntry::factory()->approved()->count(3)->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id]);
        IndicatorDataEntry::factory()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'status' => 'submitted']);

        $result = $this->service->summarize($indicator, $financialYear);

        $this->assertSame(3.0, $result['actual_value']);
    }

    public function test_no_approved_entries_yields_a_null_actual_value_except_for_count(): void
    {
        $indicator = Indicator::factory()->create(['aggregation_method' => 'sum']);
        $financialYear = FinancialYear::factory()->create();
        IndicatorDataEntry::factory()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'status' => 'draft']);

        $result = $this->service->summarize($indicator, $financialYear);

        $this->assertNull($result['actual_value']);
        $this->assertNull($result['achievement_percent']);

        $countIndicator = Indicator::factory()->create(['aggregation_method' => 'count']);
        $countResult = $this->service->summarize($countIndicator, $financialYear);
        $this->assertSame(0.0, $countResult['actual_value']);
    }

    public function test_no_targets_yields_a_null_target_and_null_achievement(): void
    {
        $indicator = Indicator::factory()->create(['aggregation_method' => 'sum']);
        $financialYear = FinancialYear::factory()->create();
        IndicatorDataEntry::factory()->approved()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'actual_value' => 42]);

        $result = $this->service->summarize($indicator, $financialYear);

        $this->assertNull($result['target_value']);
        $this->assertSame(42.0, $result['actual_value']);
        $this->assertNull($result['achievement_percent']);
    }

    public function test_entries_outside_the_financial_year_are_excluded(): void
    {
        $indicator = Indicator::factory()->create(['aggregation_method' => 'sum']);
        $financialYearA = FinancialYear::factory()->create();
        $financialYearB = FinancialYear::factory()->create();

        IndicatorDataEntry::factory()->approved()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYearA->id, 'actual_value' => 100]);
        IndicatorDataEntry::factory()->approved()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYearB->id, 'actual_value' => 999]);

        $result = $this->service->summarize($indicator, $financialYearA);

        $this->assertSame(100.0, $result['actual_value']);
    }

    public function test_monthly_range_excludes_approved_entries_outside_the_month(): void
    {
        $indicator = Indicator::factory()->create(['aggregation_method' => 'sum']);
        $financialYear = FinancialYear::factory()->create([
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 1000,
        ]);

        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2025-12-05',
            'actual_value' => 300,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2026-01-05',
            'actual_value' => 700,
        ]);

        $result = $this->service->summarize(
            $indicator,
            $financialYear,
            null,
            now()->setDate(2025, 12, 1)->startOfMonth(),
            now()->setDate(2025, 12, 1)->endOfMonth(),
        );

        $this->assertSame(300.0, $result['actual_value']);
        $this->assertSame(30.0, $result['achievement_percent']);
    }

    public function test_quarterly_summarize_prefers_period_target_and_period_actuals(): void
    {
        $indicator = Indicator::factory()->create(['aggregation_method' => 'sum']);
        $financialYear = FinancialYear::factory()->create([
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);
        $period = ReportingPeriod::factory()->create([
            'financial_year_id' => $financialYear->id,
            'name' => 'Q2',
            'start_date' => '2025-10-01',
            'end_date' => '2025-12-31',
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'reporting_period_id' => null,
            'target_value' => 1000,
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'reporting_period_id' => $period->id,
            'target_value' => 400,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'reporting_period_id' => $period->id,
            'entry_date' => '2025-11-10',
            'actual_value' => 200,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'reporting_period_id' => null,
            'entry_date' => '2026-01-10',
            'actual_value' => 800,
        ]);

        $result = $this->service->summarize($indicator, $financialYear, $period);

        $this->assertSame(400.0, $result['target_value']);
        $this->assertSame(200.0, $result['actual_value']);
        $this->assertSame(50.0, $result['achievement_percent']);
    }

    public function test_summarize_limits_approved_actuals_to_the_selected_organization(): void
    {
        $indicator = Indicator::factory()->create(['aggregation_method' => 'sum']);
        $financialYear = FinancialYear::factory()->create();
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 1000,
        ]);

        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'organization_id' => $orgA->id,
            'actual_value' => 300,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'organization_id' => $orgB->id,
            'actual_value' => 700,
        ]);

        $result = $this->service->summarize($indicator, $financialYear, organizationId: $orgA->id);

        $this->assertSame(300.0, $result['actual_value']);
        $this->assertSame(30.0, $result['achievement_percent']);

        $combined = $this->service->summarize($indicator, $financialYear, organizationId: [$orgA->id, $orgB->id]);

        $this->assertSame(1000.0, $combined['actual_value']);
    }

    public function test_analyse_summarizes_percentage_bands_and_builds_alerts(): void
    {
        $onTrack = Indicator::factory()->create(['name' => 'On track indicator', 'code' => 'A']);
        $atRisk = Indicator::factory()->create(['name' => 'At risk indicator', 'code' => 'B']);
        $offTrack = Indicator::factory()->create(['name' => 'Off track indicator', 'code' => 'C']);
        $missing = Indicator::factory()->create(['name' => 'Missing actuals', 'code' => 'D']);

        $result = $this->service->analyse([
            [
                'indicator' => $onTrack,
                'performance' => ['target_value' => 100.0, 'actual_value' => 120.0, 'achievement_percent' => 120.0, 'aggregation_method' => 'sum'],
            ],
            [
                'indicator' => $atRisk,
                'performance' => ['target_value' => 100.0, 'actual_value' => 60.0, 'achievement_percent' => 60.0, 'aggregation_method' => 'sum'],
            ],
            [
                'indicator' => $offTrack,
                'performance' => ['target_value' => 100.0, 'actual_value' => 20.0, 'achievement_percent' => 20.0, 'aggregation_method' => 'sum'],
            ],
            [
                'indicator' => $missing,
                'performance' => ['target_value' => 100.0, 'actual_value' => null, 'achievement_percent' => null, 'aggregation_method' => 'sum'],
            ],
        ]);

        $this->assertSame(4, $result['total']);
        $this->assertSame(1, $result['on_track']);
        $this->assertSame(1, $result['at_risk']);
        $this->assertSame(1, $result['off_track']);
        $this->assertSame(1, $result['no_data']);
        $this->assertSame(25.0, $result['on_track_percent']);
        $this->assertSame(66.7, $result['average_achievement']);
        $this->assertSame(['On track', 'At risk', 'Off track', 'No data'], $result['chart']['status_labels']);
        $this->assertSame([1, 1, 1, 1], $result['chart']['status_values']);
        $this->assertSame('danger', $result['alerts'][0]['level']);
        $this->assertSame('Off track', $result['alerts'][0]['title']);
        $this->assertTrue(collect($result['alerts'])->contains(fn (array $alert): bool => $alert['title'] === 'No approved actuals'));
        $this->assertSame(1, $result['alert_summary']['off_track']);
        $this->assertSame(1, $result['alert_summary']['at_risk']);
        $this->assertSame(1, $result['alert_summary']['no_actuals']);
        $this->assertSame('Average achievement by thematic area', $result['chart']['title']);
    }

    public function test_analyse_averages_achievement_by_thematic_area(): void
    {
        $household = ThematicArea::factory()->create(['name' => 'Household Economic Strengthening']);
        $protection = ThematicArea::factory()->create(['name' => 'Child Protection']);

        $result = $this->service->analyse([
            [
                'indicator' => Indicator::factory()->create(['thematic_area_id' => $household->id, 'name' => 'Savings groups', 'code' => 'HES-01']),
                'performance' => ['target_value' => 100.0, 'actual_value' => 20.0, 'achievement_percent' => 20.0, 'aggregation_method' => 'sum'],
            ],
            [
                'indicator' => Indicator::factory()->create(['thematic_area_id' => $household->id, 'name' => 'Grants', 'code' => 'HES-02']),
                'performance' => ['target_value' => 100.0, 'actual_value' => 40.0, 'achievement_percent' => 40.0, 'aggregation_method' => 'sum'],
            ],
            [
                'indicator' => Indicator::factory()->create(['thematic_area_id' => $protection->id, 'name' => 'Cases managed', 'code' => 'CP-01']),
                'performance' => ['target_value' => 100.0, 'actual_value' => 80.0, 'achievement_percent' => 80.0, 'aggregation_method' => 'sum'],
            ],
        ]);

        $this->assertSame('Average achievement by thematic area', $result['chart']['title']);
        $this->assertSame(['Household Economic Strengthening', 'Child Protection'], $result['chart']['labels']);
        $this->assertSame([30.0, 80.0], $result['chart']['values']);
        $this->assertSame(2, $result['alert_summary']['off_track']);
        $this->assertSame(1, $result['alert_summary']['at_risk']);
    }

    public function test_analyse_lists_every_scored_indicator_with_its_status(): void
    {
        $area = ThematicArea::factory()->create();
        $rows = [];

        for ($index = 1; $index <= 14; $index++) {
            $rows[] = [
                'indicator' => Indicator::factory()->create([
                    'thematic_area_id' => $area->id,
                    'name' => 'Indicator '.$index,
                    'code' => sprintf('X-%02d', $index),
                ]),
                'performance' => [
                    'target_value' => 100.0,
                    'actual_value' => (float) ($index * 10),
                    'achievement_percent' => (float) ($index * 10),
                    'aggregation_method' => 'sum',
                ],
            ];
        }

        $result = $this->service->analyse($rows);

        $this->assertSame('Achievement by indicator', $result['chart']['title']);
        $this->assertCount(14, $result['chart']['labels']);
        $this->assertSame('X-01 · Indicator 1 · Off track', $result['chart']['labels'][0]);
        $this->assertSame('X-05 · Indicator 5 · At risk', $result['chart']['labels'][4]);
        $this->assertSame('X-14 · Indicator 14 · On track', $result['chart']['labels'][13]);
        $this->assertSame([10.0, 20.0, 30.0, 40.0, 50.0, 60.0, 70.0, 80.0, 90.0, 100.0, 110.0, 120.0, 130.0, 140.0], $result['chart']['values']);
        $this->assertSame(array_fill(0, 14, '#188ae2'), $result['chart']['bar_colors']);
    }

    public function test_analyse_charts_only_scored_indicators_when_few_have_results(): void
    {
        $area = ThematicArea::factory()->create();
        $rows = [];

        for ($index = 1; $index <= 14; $index++) {
            $rows[] = [
                'indicator' => Indicator::factory()->create([
                    'thematic_area_id' => $area->id,
                    'name' => 'Indicator '.$index,
                    'code' => sprintf('X-%02d', $index),
                ]),
                'performance' => [
                    'target_value' => 100.0,
                    'actual_value' => $index <= 3 ? (float) ($index * 10) : null,
                    'achievement_percent' => $index <= 3 ? (float) ($index * 10) : null,
                    'aggregation_method' => 'sum',
                ],
            ];
        }

        $result = $this->service->analyse($rows);

        $this->assertSame('Achievement by indicator', $result['chart']['title']);
        $this->assertSame([
            'X-01 · Indicator 1 · Off track',
            'X-02 · Indicator 2 · Off track',
            'X-03 · Indicator 3 · Off track',
        ], $result['chart']['labels']);
        $this->assertSame([10.0, 20.0, 30.0], $result['chart']['values']);
        $this->assertSame(['#188ae2', '#188ae2', '#188ae2'], $result['chart']['bar_colors']);
    }

    public function test_prioritize_off_track_rows_puts_the_weakest_indicators_first(): void
    {
        $onTrack = Indicator::factory()->create(['name' => 'AAA on track', 'code' => 'A']);
        $atRisk = Indicator::factory()->create(['name' => 'MMM at risk', 'code' => 'M']);
        $offTrackLow = Indicator::factory()->create(['name' => 'ZZZ off track low', 'code' => 'Z']);
        $offTrackHigh = Indicator::factory()->create(['name' => 'YYY off track high', 'code' => 'Y']);
        $noData = Indicator::factory()->create(['name' => 'BBB no data', 'code' => 'B']);

        $sorted = $this->service->prioritizeOffTrackRows([
            [
                'indicator' => $onTrack,
                'performance' => ['target_value' => 100.0, 'actual_value' => 120.0, 'achievement_percent' => 120.0, 'aggregation_method' => 'sum'],
            ],
            [
                'indicator' => $atRisk,
                'performance' => ['target_value' => 100.0, 'actual_value' => 60.0, 'achievement_percent' => 60.0, 'aggregation_method' => 'sum'],
            ],
            [
                'indicator' => $offTrackHigh,
                'performance' => ['target_value' => 100.0, 'actual_value' => 40.0, 'achievement_percent' => 40.0, 'aggregation_method' => 'sum'],
            ],
            [
                'indicator' => $offTrackLow,
                'performance' => ['target_value' => 100.0, 'actual_value' => 10.0, 'achievement_percent' => 10.0, 'aggregation_method' => 'sum'],
            ],
            [
                'indicator' => $noData,
                'performance' => ['target_value' => 100.0, 'actual_value' => null, 'achievement_percent' => null, 'aggregation_method' => 'sum'],
            ],
        ]);

        $this->assertSame(
            ['ZZZ off track low', 'YYY off track high', 'MMM at risk', 'AAA on track', 'BBB no data'],
            array_map(fn (array $row): string => $row['indicator']->name, $sorted),
        );
    }
}
