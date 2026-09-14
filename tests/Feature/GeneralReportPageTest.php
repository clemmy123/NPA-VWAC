<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use App\Models\Project;
use App\Models\ReportingPeriod;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralReportPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_unauthorized_user_cannot_open_the_general_report(): void
    {
        $user = $this->userWithRole('Data Entry User');

        $this->actingAs($user)->get(route('reports.general'))->assertForbidden();
    }

    public function test_authorized_user_sees_the_filter_bar_and_frequency_pills(): void
    {
        $user = $this->userWithRole('Super Admin');

        $response = $this->actingAs($user)->get(route('reports.general'));

        $response->assertOk();
        $response->assertSee('Monthly Reports');
        $response->assertSee('Plan');
        $response->assertSee('Thematic Area');
        $response->assertSee('Filter');
        $response->assertSee('Reset');
        $response->assertSee('>Monthly</a>', false);
        $response->assertSee('>Quarterly</a>', false);
        $response->assertSee('>Yearly</a>', false);
        $response->assertSee('click Filter to see monitoring and evaluation analysis');
        $response->assertDontSee('Monitoring & Evaluation');
    }

    public function test_monthly_report_shows_approved_actuals_for_the_selected_month_only(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create(['name' => 'NPA-VAWC']);
        $thematicArea = ThematicArea::factory()->create([
            'project_id' => $project->id,
            'name' => 'Household Economic Strengthening',
        ]);
        $indicator = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Women reached with savings groups',
            'code' => 'HES-01',
            'aggregation_method' => 'sum',
        ]);
        $financialYear = FinancialYear::factory()->create([
            'name' => '2025/26',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 1000,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2025-12-10',
            'actual_value' => 400,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2026-01-10',
            'actual_value' => 200,
        ]);
        IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2025-12-15',
            'actual_value' => 999,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->get(route('reports.general', [
            'frequency' => 'monthly',
            'month' => '2025-12-01',
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'apply' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Women reached with savings groups');
        $response->assertSee('400</td>', false);
        $response->assertDontSee('200</td>', false);
        $response->assertDontSee('999</td>', false);
        $response->assertSee('40%');
        $response->assertSee('Monitoring & Evaluation');
        $response->assertSee('Off track');
        $response->assertSee('id="report-achievement-chart"', false);
        $response->assertSee('id="report-status-chart"', false);
    }

    public function test_quarterly_and_yearly_tabs_swap_the_period_filter(): void
    {
        $user = $this->userWithRole('Super Admin');

        $this->actingAs($user)
            ->get(route('reports.general', ['frequency' => 'quarterly']))
            ->assertOk()
            ->assertSee('Quarterly Reports')
            ->assertSee('>Quarter</label>', false);

        $this->actingAs($user)
            ->get(route('reports.general', ['frequency' => 'yearly']))
            ->assertOk()
            ->assertSee('Yearly Reports')
            ->assertSee('>Year</label>', false);
    }

    public function test_project_manager_can_open_the_general_report(): void
    {
        $user = $this->userWithRole('Project Manager');

        $this->actingAs($user)->get(route('reports.general'))->assertOk();
    }

    public function test_yearly_report_sums_approved_actuals_across_the_financial_year(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id]);
        $indicator = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Women reached with savings groups',
            'aggregation_method' => 'sum',
        ]);
        $financialYear = FinancialYear::factory()->create([
            'name' => '2025/26',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 1000,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2025-12-10',
            'actual_value' => 400,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2026-01-10',
            'actual_value' => 200,
        ]);

        $response = $this->actingAs($user)->get(route('reports.general', [
            'frequency' => 'yearly',
            'financial_year_id' => $financialYear->id,
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'apply' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('600</td>', false);
        $response->assertSee('60%');
    }

    public function test_quarterly_report_uses_the_period_target_and_period_actuals(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id]);
        $indicator = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Women reached with savings groups',
            'aggregation_method' => 'sum',
        ]);
        $financialYear = FinancialYear::factory()->create([
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
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
            'reporting_period_id' => $period->id,
            'target_value' => 500,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'reporting_period_id' => $period->id,
            'entry_date' => '2025-11-10',
            'actual_value' => 250,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => '2026-01-10',
            'actual_value' => 800,
        ]);

        $response = $this->actingAs($user)->get(route('reports.general', [
            'frequency' => 'quarterly',
            'reporting_period_id' => $period->id,
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'apply' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('250</td>', false);
        $response->assertDontSee('800</td>', false);
        $response->assertSee('50%');
    }
}
