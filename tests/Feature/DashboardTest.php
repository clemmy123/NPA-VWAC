<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorTarget;
use App\Models\Project;
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

    public function test_dashboard_shows_target_vs_actual_for_the_current_financial_year(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $financialYear = FinancialYear::factory()->create(['name' => '2026/27', 'is_current' => true]);
        $project = Project::factory()->create(['name' => 'Community Sensitization']);
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id, 'name' => 'Prevention']);
        $indicator = Indicator::factory()->create(['thematic_area_id' => $thematicArea->id, 'name' => 'Number of dialogues held', 'aggregation_method' => 'sum']);
        IndicatorTarget::factory()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'target_value' => 100]);
        IndicatorDataEntry::factory()->approved()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'actual_value' => 40]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Community Sensitization');
        $response->assertSee('Prevention');
        $response->assertSee('Number of dialogues held');
        $response->assertSee('40');
        $response->assertSee('100');
    }

    public function test_dashboard_scopes_projects_to_the_users_assignments(): void
    {
        $pm = $this->userWithRole('Project Manager');
        $financialYear = FinancialYear::factory()->create(['is_current' => true]);
        $assigned = Project::factory()->create(['name' => 'My Project']);
        $other = Project::factory()->create(['name' => 'Not My Project']);
        $assigned->users()->attach($pm->id, ['is_active' => true]);

        $response = $this->actingAs($pm)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('My Project');
        $response->assertDontSee('Not My Project');
    }

    public function test_switching_financial_year_changes_the_figures_shown(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $thematicArea = ThematicArea::factory()->create();
        $indicator = Indicator::factory()->create(['thematic_area_id' => $thematicArea->id, 'aggregation_method' => 'sum']);
        $yearA = FinancialYear::factory()->create();
        $yearB = FinancialYear::factory()->create();
        IndicatorTarget::factory()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $yearA->id, 'target_value' => 10]);
        IndicatorTarget::factory()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $yearB->id, 'target_value' => 999]);

        $response = $this->actingAs($admin)->get(route('dashboard', ['financial_year_id' => $yearB->id]));

        $response->assertOk();
        $response->assertSee('999');
        $response->assertDontSee('10.00');
    }
}
