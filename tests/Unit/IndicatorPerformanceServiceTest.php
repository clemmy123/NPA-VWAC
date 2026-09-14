<?php

namespace Tests\Unit;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use App\Models\ReportingPeriod;
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
    }
}
