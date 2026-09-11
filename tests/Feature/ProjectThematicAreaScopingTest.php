<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectThematicAreaScopingTest extends TestCase
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

    public function test_project_manager_only_sees_assigned_projects_in_the_index(): void
    {
        $pm = $this->userWithRole('Project Manager');
        $assigned = Project::factory()->create(['name' => 'Assigned Project']);
        $other = Project::factory()->create(['name' => 'Other Project']);
        $assigned->users()->attach($pm->id, ['is_active' => true]);

        $response = $this->actingAs($pm)->get(route('projects.index'));

        $response->assertOk();
        $response->assertSee('Assigned Project');
        $response->assertDontSee('Other Project');
    }

    public function test_project_manager_cannot_view_an_unassigned_project(): void
    {
        $pm = $this->userWithRole('Project Manager');
        $other = Project::factory()->create();

        $response = $this->actingAs($pm)->get(route('projects.show', $other));

        $response->assertForbidden();
    }

    public function test_super_admin_sees_every_project_regardless_of_assignment(): void
    {
        $admin = $this->userWithRole('Super Admin');
        Project::factory()->create(['name' => 'Unassigned Project']);

        $response = $this->actingAs($admin)->get(route('projects.index'));

        $response->assertOk();
        $response->assertSee('Unassigned Project');
    }

    public function test_thematic_manager_only_sees_directly_assigned_thematic_areas(): void
    {
        $tm = $this->userWithRole('Thematic Manager');
        $assigned = ThematicArea::factory()->create(['name' => 'Assigned Theme']);
        $other = ThematicArea::factory()->create(['name' => 'Other Theme']);
        $assigned->users()->attach($tm->id, ['is_active' => true]);

        $response = $this->actingAs($tm)->get(route('thematic-areas.index'));

        $response->assertOk();
        $response->assertSee('Assigned Theme');
        $response->assertDontSee('Other Theme');
    }

    public function test_project_manager_sees_thematic_areas_under_their_assigned_project(): void
    {
        $pm = $this->userWithRole('Project Manager');
        $project = Project::factory()->create();
        $project->users()->attach($pm->id, ['is_active' => true]);
        $thematicArea = ThematicArea::factory()->create(['project_id' => $project->id, 'name' => 'Under My Project']);
        $unrelated = ThematicArea::factory()->create(['name' => 'Unrelated Theme']);

        $response = $this->actingAs($pm)->get(route('thematic-areas.index'));

        $response->assertOk();
        $response->assertSee('Under My Project');
        $response->assertDontSee('Unrelated Theme');
    }

    public function test_thematic_manager_cannot_view_an_unassigned_thematic_area(): void
    {
        $tm = $this->userWithRole('Thematic Manager');
        $other = ThematicArea::factory()->create();

        $response = $this->actingAs($tm)->get(route('thematic-areas.show', $other));

        $response->assertForbidden();
    }

    public function test_super_admin_can_assign_a_project_manager(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $pm = $this->userWithRole('Project Manager');
        $project = Project::factory()->create();

        $response = $this->actingAs($admin)->post(route('projects.managers.store', $project), [
            'user_id' => $pm->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_users', [
            'project_id' => $project->id,
            'user_id' => $pm->id,
            'is_active' => true,
        ]);
    }

    public function test_project_manager_cannot_assign_themselves_to_an_unrelated_project(): void
    {
        $pm = $this->userWithRole('Project Manager');
        $project = Project::factory()->create();

        $response = $this->actingAs($pm)->post(route('projects.managers.store', $project), [
            'user_id' => $pm->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('project_users', [
            'project_id' => $project->id,
            'user_id' => $pm->id,
        ]);
    }

    public function test_project_manager_can_assign_a_co_manager_to_their_own_project(): void
    {
        $pm = $this->userWithRole('Project Manager');
        $coManager = $this->userWithRole('Project Manager');
        $project = Project::factory()->create();
        $project->users()->attach($pm->id, ['is_active' => true]);

        $response = $this->actingAs($pm)->post(route('projects.managers.store', $project), [
            'user_id' => $coManager->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_users', [
            'project_id' => $project->id,
            'user_id' => $coManager->id,
        ]);
    }

    public function test_super_admin_can_assign_a_thematic_manager(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $tm = $this->userWithRole('Thematic Manager');
        $thematicArea = ThematicArea::factory()->create();

        $response = $this->actingAs($admin)->post(route('thematic-areas.managers.store', $thematicArea), [
            'user_id' => $tm->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('thematic_area_users', [
            'thematic_area_id' => $thematicArea->id,
            'user_id' => $tm->id,
            'is_active' => true,
        ]);
    }

    public function test_project_manager_can_remove_a_manager_from_their_own_project(): void
    {
        $pm = $this->userWithRole('Project Manager');
        $other = $this->userWithRole('Project Manager');
        $project = Project::factory()->create();
        $project->users()->attach([$pm->id, $other->id], ['is_active' => true]);

        $response = $this->actingAs($pm)->delete(route('projects.managers.destroy', [$project, $other]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('project_users', [
            'project_id' => $project->id,
            'user_id' => $other->id,
        ]);
    }
}
