<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\ReportingPeriod;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LateDataEntryReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_it_lists_an_assignment_with_no_submitted_entry_for_a_closed_period(): void
    {
        $financialYear = FinancialYear::factory()->current()->started()->create();
        $closedPeriod = ReportingPeriod::factory()->create([
            'financial_year_id' => $financialYear->id,
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subDay(),
        ]);

        $indicator = Indicator::factory()->create();
        $entrant = User::factory()->create();
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $entrant->id,
        ]);

        $reviewer = User::factory()->create();
        $reviewer->assignRole('Thematic Manager');

        $response = $this->actingAs($reviewer)->get('/reports/late-data-entries');

        $response->assertOk();
        $response->assertSee($indicator->name);
        $response->assertSee($closedPeriod->name);
    }

    public function test_it_does_not_list_an_assignment_that_already_has_a_submitted_entry(): void
    {
        $financialYear = FinancialYear::factory()->current()->started()->create();
        $closedPeriod = ReportingPeriod::factory()->create([
            'financial_year_id' => $financialYear->id,
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subDay(),
        ]);

        $indicator = Indicator::factory()->create();
        $entrant = User::factory()->create();
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $entrant->id,
        ]);

        IndicatorDataEntry::factory()->submitted()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'reporting_period_id' => $closedPeriod->id,
            'entered_by' => $entrant->id,
        ]);

        $reviewer = User::factory()->create();
        $reviewer->assignRole('Thematic Manager');

        $response = $this->actingAs($reviewer)->get('/reports/late-data-entries');

        $response->assertOk();
        $response->assertDontSee($indicator->name);
    }
}
