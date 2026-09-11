<?php

namespace Tests\Unit;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
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
}
