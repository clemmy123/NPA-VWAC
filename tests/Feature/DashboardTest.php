<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use App\Models\Project;
use App\Models\Region;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_dashboard_shows_plan_filters_summary_and_region_chart(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $financialYear = FinancialYear::factory()->create([
            'name' => '2026/27',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_current' => true,
        ]);
        $project = Project::factory()->create([
            'name' => 'NPA-VAWC',
            'description' => 'A comprehensive program targeting leadership training, mentorship, and empowerment of women in rural and urban communities.',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);
        $thematicArea = ThematicArea::factory()->create([
            'project_id' => $project->id,
            'name' => 'Household Economic Strengthening',
            'description' => 'Increase women leadership and participation in local communities',
        ]);
        $indicator = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Percentage of Households below the National Basic Needs Poverty Line',
            'aggregation_method' => 'average',
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 25,
        ]);
        $dodoma = Region::factory()->create(['name' => 'Dodoma']);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'location_level' => 'region',
            'location_id' => $dodoma->region_id,
            'actual_value' => 7.5,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertSee('>Dashboard</h4>', false);
        $response->assertSee('>General Overview</li>', false);
        $response->assertSee('>Plan</label>', false);
        $response->assertSee('>Thematic Area</label>', false);
        $response->assertSee('>Indicator</label>', false);
        $response->assertDontSee('>Filter</button>', false);
        $response->assertDontSee('>Reset</a>', false);
        $response->assertSee('NPA-VAWC');
        $response->assertSee('Household Economic Strengthening');
        $response->assertSee('Percentage of Households below the National Basic Needs Poverty Line');
        $response->assertSee('Plan Period');
        $response->assertSee('2026/27');
        $response->assertSee('01 Jul 2026');
        $response->assertSee('30 Jun 2027');
        $response->assertDontSee('1/1/2025');
        $response->assertDontSee('12/31/2025');
        $response->assertSee('Main Target');
        $response->assertSee('Increase women leadership and participation in local communities');
        $response->assertSee('Plan Description');
        $response->assertSee('30% reached');
        $response->assertSee('Data by Region');
        $response->assertSee('Dodoma');
        $response->assertSee('id="dashboard-region-chart"', false);
        $response->assertSee('gridLines: { display: false }', false);
        $response->assertDontSee('Visible collections');
        $response->assertDontSee('Recent data collections');
        $response->assertDontSee('Awaiting review');
    }

    public function test_dashboard_scopes_projects_to_the_users_assignments(): void
    {
        $pm = $this->userWithRole('Project Manager');
        FinancialYear::factory()->create(['is_current' => true]);
        $assigned = Project::factory()->create(['name' => 'My Project']);
        Project::factory()->create(['name' => 'Not My Project']);
        $assigned->users()->attach($pm->id, ['is_active' => true]);

        $response = $this->actingAs($pm)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('My Project');
        $response->assertDontSee('Not My Project');
    }

    public function test_dashboard_switches_the_selected_indicator_from_the_query_string(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $financialYear = FinancialYear::factory()->create(['is_current' => true]);
        $project = Project::factory()->create();
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id]);
        $first = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Number of dialogues held',
            'code' => 'HES-01',
        ]);
        $second = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Number of Women-owned SMEs',
            'code' => 'HES-03',
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $second->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 500,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard', [
            'project_id' => $project->id,
            'thematic_area_id' => $thematicArea->id,
            'indicator_id' => $second->id,
        ]));

        $response->assertOk();
        $response->assertSee($first->name);
        $response->assertSee($second->name);
        $response->assertSee('value="'.$second->id.'"', false);
    }

    public function test_data_entry_user_dashboard_shows_only_assigned_active_indicators(): void
    {
        $user = $this->userWithRole('Data Entry User');
        FinancialYear::factory()->create(['is_current' => true]);
        $assigned = Indicator::factory()->create(['name' => 'Assigned dashboard indicator', 'status' => 'active']);
        $other = Indicator::factory()->create(['name' => 'Available dashboard indicator', 'status' => 'active']);
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $assigned->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($assigned->name);
        $response->assertDontSee($other->name);
    }

    public function test_dashboard_is_not_restricted_by_legacy_region_assignments(): void
    {
        $collector = $this->userWithRole('Data Entry User');
        $financialYear = FinancialYear::factory()->create(['is_current' => true]);
        $indicator = Indicator::factory()->create([
            'name' => 'Scoped district collections',
            'status' => 'active',
            'requires_location' => true,
            'reporting_location_level' => 'district',
            'aggregation_method' => 'sum',
        ]);
        $assignedRegion = Region::factory()->create(['name' => 'Assigned Region']);
        $otherRegion = Region::factory()->create(['name' => 'Other Region']);
        $insideDistrict = District::factory()->create(['name' => 'Inside District', 'region_id' => $assignedRegion->region_id]);
        $outsideDistrict = District::factory()->create(['name' => 'Outside District', 'region_id' => $otherRegion->region_id]);
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $collector->id,
            'location_level' => 'region',
            'location_id' => $assignedRegion->region_id,
        ]);
        IndicatorTarget::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 100,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'location_level' => 'district',
            'location_id' => $insideDistrict->district_id,
            'actual_value' => 10,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'location_level' => 'district',
            'location_id' => $outsideDistrict->district_id,
            'actual_value' => 90,
        ]);

        $response = $this->actingAs($collector)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('100% reached');
        $response->assertSee('Inside District');
        $response->assertSee('Outside District');
        $response->assertSee('Assigned Region');
        $response->assertSee('Other Region');
    }
}
