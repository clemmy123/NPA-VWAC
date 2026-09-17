<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorBaseline;
use App\Models\IndicatorTarget;
use App\Models\Project;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanBuilderControllerTest extends TestCase
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

    public function test_thematic_manager_only_sees_their_assigned_thematic_areas_in_the_picker(): void
    {
        $tm = $this->userWithRole('Thematic Manager');
        $mine = ThematicArea::factory()->create(['name' => 'My Thematic Area']);
        $other = ThematicArea::factory()->create(['name' => 'Someone Elses Area']);
        $mine->users()->attach($tm->id, ['is_active' => true]);

        $response = $this->actingAs($tm)->get(route('plan-builder.index'));

        $response->assertOk();
        $response->assertSee('My Thematic Area');
        $response->assertDontSee('Someone Elses Area');
    }

    public function test_thematic_manager_cannot_open_an_unassigned_thematic_area(): void
    {
        $tm = $this->userWithRole('Thematic Manager');
        $other = ThematicArea::factory()->create();

        $response = $this->actingAs($tm)->get(route('plan-builder.show', $other));

        $response->assertForbidden();
    }

    public function test_project_manager_sees_thematic_areas_under_their_assigned_project(): void
    {
        $pm = $this->userWithRole('Project Manager');
        $project = Project::factory()->create();
        $project->users()->attach($pm->id, ['is_active' => true]);
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id, 'name' => 'Project Scoped Area']);

        $response = $this->actingAs($pm)->get(route('plan-builder.index'));

        $response->assertOk();
        $response->assertSee('Project Scoped Area');
    }

    public function test_super_admin_sees_every_thematic_area_regardless_of_assignment(): void
    {
        $admin = $this->userWithRole('Super Admin');
        ThematicArea::factory()->create(['name' => 'Unassigned Area']);

        $response = $this->actingAs($admin)->get(route('plan-builder.index'));

        $response->assertOk();
        $response->assertSee('Unassigned Area');
    }

    public function test_the_project_filter_only_appears_when_more_than_one_project_is_visible(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();
        ThematicArea::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($admin)->get(route('plan-builder.index'));

        $response->assertOk();
        $response->assertDontSee('plan-project-filter', false);
    }

    public function test_the_project_filter_narrows_the_picker_to_one_project(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        $inA = ThematicArea::factory()->create(['project_id' => $projectA->id, 'name' => 'In Project A']);
        $inB = ThematicArea::factory()->create(['project_id' => $projectB->id, 'name' => 'In Project B']);

        $response = $this->actingAs($admin)->get(route('plan-builder.index'));
        $response->assertSee('plan-project-filter', false);

        $response = $this->actingAs($admin)->get(route('plan-builder.index', ['project_id' => $projectA->id]));

        $response->assertOk();
        $response->assertSee('In Project A');
        $response->assertDontSee('In Project B');
    }

    public function test_the_builder_page_shows_indicators_with_their_current_financial_year_baseline_and_target(): void
    {
        $tm = $this->userWithRole('Thematic Manager');
        $thematicArea = ThematicArea::factory()->create();
        $thematicArea->users()->attach($tm->id, ['is_active' => true]);
        $financialYear = FinancialYear::factory()->create(['is_current' => true]);
        $indicator = Indicator::factory()->create(['thematic_area_id' => $thematicArea->id, 'name' => 'Boreholes rehabilitated']);
        IndicatorBaseline::factory()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'baseline_value' => 4]);
        IndicatorTarget::factory()->create(['indicator_id' => $indicator->id, 'financial_year_id' => $financialYear->id, 'reporting_period_id' => null, 'target_value' => 18]);

        $response = $this->actingAs($tm)->get(route('plan-builder.show', $thematicArea));

        $response->assertOk();
        $response->assertSee('Boreholes rehabilitated');
        $response->assertSee('value="4"', false);
        $response->assertSee('value="18"', false);
    }

    public function test_a_new_target_is_restricted_to_the_current_financial_year(): void
    {
        $tm = $this->userWithRole('Thematic Manager');
        $thematicArea = ThematicArea::factory()->create();
        $thematicArea->users()->attach($tm->id, ['is_active' => true]);
        $currentYear = FinancialYear::factory()->create(['is_current' => true, 'name' => '2026/27']);
        $otherYear = FinancialYear::factory()->create(['is_current' => false, 'name' => '2027/28']);
        $indicator = Indicator::factory()->create(['thematic_area_id' => $thematicArea->id]);

        $response = $this->actingAs($tm)->get(route('plan-builder.show', $thematicArea));

        $response->assertOk();
        $response->assertSee('pb-target-fy', false);
        $response->assertSee('2026/27');
        $response->assertSee('2027/28');

        $response = $this->actingAs($tm)->postJson(route('indicator-targets.store'), [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $otherYear->id,
            'target_value' => 25,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['financial_year_id']);
        $this->assertDatabaseMissing('indicator_targets', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $otherYear->id,
        ]);
    }

    public function test_a_new_indicator_can_be_created_via_json_from_the_builder_page(): void
    {
        $tm = $this->userWithRole('Thematic Manager');
        $thematicArea = ThematicArea::factory()->create();
        $thematicArea->users()->attach($tm->id, ['is_active' => true]);

        $response = $this->actingAs($tm)->postJson(route('indicators.store'), [
            'thematic_area_id' => $thematicArea->id,
            'code' => 'BOREHOLE-ABC12',
            'name' => 'Boreholes rehabilitated',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('indicators', [
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Boreholes rehabilitated',
        ]);
    }
}
