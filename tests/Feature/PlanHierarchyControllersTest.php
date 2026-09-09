<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\Intervention;
use App\Models\Project;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanHierarchyControllersTest extends TestCase
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

    // --- Project ---

    public function test_authorized_user_can_create_a_project(): void
    {
        $user = $this->userWithRole('Super Admin');

        $response = $this->actingAs($user)->postJson('/projects', [
            'code' => 'NPA-VWAC-II',
            'name' => 'NPA VAWC II',
            'start_date' => '2026-07-01',
            'end_date' => '2031-06-30',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('projects', ['code' => 'NPA-VWAC-II']);
    }

    public function test_unauthorized_user_cannot_create_a_project(): void
    {
        $user = $this->userWithRole('Data Entry User');

        $response = $this->actingAs($user)->postJson('/projects', [
            'code' => 'NPA-VWAC-II',
            'name' => 'NPA VAWC II',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('projects', ['code' => 'NPA-VWAC-II']);
    }

    public function test_project_creation_rejects_duplicate_code(): void
    {
        $user = $this->userWithRole('Super Admin');
        Project::factory()->create(['code' => 'DUP-CODE']);

        $response = $this->actingAs($user)->postJson('/projects', [
            'code' => 'DUP-CODE',
            'name' => 'Another Project',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    public function test_deleting_a_project_soft_deletes_it(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/projects/{$project->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    // --- Thematic Area ---

    public function test_authorized_user_can_create_a_thematic_area(): void
    {
        $user = $this->userWithRole('Project Manager');
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->postJson('/thematic-areas', [
            'project_id' => $project->id,
            'name' => 'Prevention',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('thematic_areas', ['name' => 'Prevention', 'project_id' => $project->id]);
    }

    public function test_unauthorized_user_cannot_create_a_thematic_area(): void
    {
        $user = $this->userWithRole('Data Entry User');
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->postJson('/thematic-areas', [
            'project_id' => $project->id,
            'name' => 'Prevention',
        ]);

        $response->assertForbidden();
    }

    // --- Indicator ---

    public function test_authorized_user_can_create_an_indicator(): void
    {
        $user = $this->userWithRole('Thematic Manager');
        $thematicArea = ThematicArea::factory()->create();

        $response = $this->actingAs($user)->postJson('/indicators', [
            'thematic_area_id' => $thematicArea->id,
            'code' => 'IND-001',
            'name' => 'Number of survivors supported',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('indicators', ['code' => 'IND-001', 'thematic_area_id' => $thematicArea->id]);
    }

    public function test_duplicate_indicator_code_within_same_thematic_area_is_rejected(): void
    {
        $user = $this->userWithRole('Thematic Manager');
        $thematicArea = ThematicArea::factory()->create();
        Indicator::factory()->create(['thematic_area_id' => $thematicArea->id, 'code' => 'IND-001']);

        $response = $this->actingAs($user)->postJson('/indicators', [
            'thematic_area_id' => $thematicArea->id,
            'code' => 'IND-001',
            'name' => 'Duplicate code',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    public function test_same_indicator_code_is_allowed_under_a_different_thematic_area(): void
    {
        $user = $this->userWithRole('Thematic Manager');
        $thematicAreaA = ThematicArea::factory()->create();
        $thematicAreaB = ThematicArea::factory()->create();
        Indicator::factory()->create(['thematic_area_id' => $thematicAreaA->id, 'code' => 'IND-001']);

        $response = $this->actingAs($user)->postJson('/indicators', [
            'thematic_area_id' => $thematicAreaB->id,
            'code' => 'IND-001',
            'name' => 'Same code, different thematic area',
        ]);

        $response->assertCreated();
    }

    public function test_unauthorized_user_cannot_create_an_indicator(): void
    {
        $user = $this->userWithRole('Data Entry User');
        $thematicArea = ThematicArea::factory()->create();

        $response = $this->actingAs($user)->postJson('/indicators', [
            'thematic_area_id' => $thematicArea->id,
            'code' => 'IND-001',
            'name' => 'Number of survivors supported',
        ]);

        $response->assertForbidden();
    }

    // --- Intervention ---

    public function test_intervention_indicator_relationship_works_both_directions(): void
    {
        $user = $this->userWithRole('Thematic Manager');
        $thematicArea = ThematicArea::factory()->create();
        $indicatorA = Indicator::factory()->create(['thematic_area_id' => $thematicArea->id]);
        $indicatorB = Indicator::factory()->create(['thematic_area_id' => $thematicArea->id]);

        $response = $this->actingAs($user)->postJson('/interventions', [
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Community dialogues',
            'indicator_ids' => [$indicatorA->id, $indicatorB->id],
        ]);

        $response->assertCreated();
        $interventionId = $response->json('data.id');

        $intervention = Intervention::find($interventionId);
        $this->assertCount(2, $intervention->indicators);

        $this->assertTrue($indicatorA->fresh()->interventions->contains('id', $interventionId));
        $this->assertTrue($indicatorB->fresh()->interventions->contains('id', $interventionId));

        // one indicator linked to multiple interventions
        $secondIntervention = Intervention::factory()->create(['thematic_area_id' => $thematicArea->id]);
        $secondIntervention->indicators()->attach($indicatorA->id);

        $this->assertCount(2, $indicatorA->fresh()->interventions);
    }

    public function test_unauthorized_user_cannot_create_an_intervention(): void
    {
        $user = $this->userWithRole('Data Entry User');
        $thematicArea = ThematicArea::factory()->create();

        $response = $this->actingAs($user)->postJson('/interventions', [
            'thematic_area_id' => $thematicArea->id,
            'name' => 'Community dialogues',
        ]);

        $response->assertForbidden();
    }
}
