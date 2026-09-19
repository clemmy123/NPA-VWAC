<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Project;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkstationReportPageTest extends TestCase
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

    public function test_unauthorized_user_cannot_open_the_workstation_report(): void
    {
        $user = $this->userWithRole('Data Entry User');

        $this->actingAs($user)->get(route('reports.workstation'))->assertForbidden();
    }

    public function test_authorized_user_sees_the_toolbar_filters_and_frequency_pills(): void
    {
        $user = $this->userWithRole('Super Admin');
        OrganizationType::factory()->create(['name' => 'Bank / Financial Institution', 'is_active' => true]);
        OrganizationType::factory()->create(['name' => 'Inactive Type', 'is_active' => false]);

        $response = $this->actingAs($user)->get(route('reports.workstation'));

        $response->assertOk();
        $response->assertSee('Monthly Workstation Reports');
        $response->assertSee('filter-card', false);
        $response->assertSee('All Workstation Types');
        $response->assertSee('All Plans');
        $response->assertSee('All Thematic Areas');
        $response->assertSee('Bank / Financial Institution');
        $response->assertDontSee('Inactive Type');
        $response->assertSee('id="organization_type_id"', false);
        $response->assertSee('id="project_id"', false);
        $response->assertSee('id="thematic_area_id"', false);
        $response->assertSee('placeholder="Search..."', false);
        $response->assertSee('minimumResultsForSearch', false);
        $response->assertSee('>Filter</button>', false);
        $response->assertSee('>Reset</a>', false);
        $response->assertSee('>Monthly</a>', false);
        $response->assertSee('>Quarterly</a>', false);
        $response->assertSee('>Yearly</a>', false);
        $response->assertSee('Apply filters to see analysis.');
        $response->assertDontSee('Monitoring & Evaluation');
    }

    public function test_monthly_report_counts_approved_actuals_for_the_searched_workstation_only(): void
    {
        $user = $this->userWithRole('Super Admin');
        $orgA = Organization::factory()->create(['name' => 'CRDB Bank']);
        $orgB = Organization::factory()->create(['name' => 'NMB Bank']);
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
            'organization_id' => $orgA->id,
            'entry_date' => '2025-12-10',
            'actual_value' => 400,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'organization_id' => $orgB->id,
            'entry_date' => '2025-12-10',
            'actual_value' => 200,
        ]);
        IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'organization_id' => $orgA->id,
            'entry_date' => '2025-12-15',
            'actual_value' => 999,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->get(route('reports.workstation', [
            'frequency' => 'monthly',
            'month' => '2025-12-01',
            'search' => 'CRDB',
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'apply' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Women reached with savings groups');
        $response->assertSee('CRDB Bank');
        $response->assertSee('400</td>', false);
        $response->assertDontSee('200</td>', false);
        $response->assertDontSee('999</td>', false);
        $response->assertSee('40%');
        $response->assertSee('Monitoring & Evaluation');
        $response->assertSee('Off track');
        $response->assertSee('id="report-achievement-chart"', false);
        $response->assertSee('id="report-status-chart"', false);
    }

    public function test_monthly_report_aggregates_approved_actuals_for_the_selected_workstation_type(): void
    {
        $user = $this->userWithRole('Super Admin');
        $bankType = OrganizationType::factory()->create(['name' => 'Bank / Financial Institution']);
        $ngoType = OrganizationType::factory()->create(['name' => 'Non-Governmental Organization']);
        $orgA = Organization::factory()->create(['name' => 'CRDB Bank', 'organization_type_id' => $bankType->id]);
        $orgB = Organization::factory()->create(['name' => 'TAWLA', 'organization_type_id' => $ngoType->id]);
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
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 1000,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'organization_id' => $orgA->id,
            'entry_date' => '2025-12-10',
            'actual_value' => 400,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'organization_id' => $orgB->id,
            'entry_date' => '2025-12-10',
            'actual_value' => 200,
        ]);

        $response = $this->actingAs($user)->get(route('reports.workstation', [
            'frequency' => 'monthly',
            'month' => '2025-12-01',
            'organization_type_id' => $bankType->id,
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'apply' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Bank / Financial Institution');
        $response->assertSee('400</td>', false);
        $response->assertDontSee('200</td>', false);
        $response->assertSee('40%');
    }

    public function test_project_manager_can_open_the_workstation_report(): void
    {
        $user = $this->userWithRole('Project Manager');

        $this->actingAs($user)->get(route('reports.workstation'))->assertOk();
    }

    public function test_indicator_results_table_is_paginated(): void
    {
        $user = $this->userWithRole('Super Admin');
        Organization::factory()->create();
        $project = Project::factory()->create();
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id]);
        $financialYear = FinancialYear::factory()->create([
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        foreach (range(1, 16) as $index) {
            $indicator = Indicator::factory()->create([
                'thematic_area_id' => $thematicArea->id,
                'name' => 'Workstation indicator '.$index,
                'code' => sprintf('WS-%02d', $index),
                'aggregation_method' => 'sum',
            ]);
            IndicatorTarget::factory()->create([
                'indicator_id' => $indicator->id,
                'financial_year_id' => $financialYear->id,
                'target_value' => 100,
            ]);
        }

        $pageOne = $this->actingAs($user)->get(route('reports.workstation', [
            'frequency' => 'monthly',
            'month' => '2025-12-01',
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'apply' => 1,
        ]));

        $pageOne->assertOk();
        $pageOne->assertSee('id="results"', false);
        $pageOne->assertDontSee('Previous Result');
        $pageOne->assertSee('>Workstation indicator 1</div>', false);
        $pageOne->assertDontSee('>Workstation indicator 11</div>', false);
        $pageOne->assertSee('page=2', false);
        $pageOne->assertSee('#results', false);
        $pageOne->assertSee('Showing');
        $pageOne->assertSee('>10</span>', false);
        $pageOne->assertSee('>16</span>', false);

        $pageTwo = $this->actingAs($user)->get(route('reports.workstation', [
            'frequency' => 'monthly',
            'month' => '2025-12-01',
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'apply' => 1,
            'page' => 2,
        ]));

        $pageTwo->assertOk();
        $pageTwo->assertSee('>Workstation indicator 16</div>', false);
        $pageTwo->assertDontSee('>Workstation indicator 1</div>', false);
    }
}
