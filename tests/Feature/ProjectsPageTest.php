<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsPageTest extends TestCase
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

    public function test_authorized_user_sees_the_projects_table_page(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create(['name' => 'NPA VAWC II', 'code' => 'NPA-II']);

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertOk();
        $response->assertSee('table-card', false);
        $response->assertSee($project->name);
        $response->assertSee($project->code);
        $response->assertSee(route('projects.create'), false);
    }

    public function test_unauthorized_user_is_forbidden_from_the_projects_page(): void
    {
        $user = $this->userWithRole('Data Entry User');

        $response = $this->actingAs($user)->get(route('projects.index'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_create_a_project_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'code' => 'NPA-III',
            'name' => 'NPA VAWC III',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('projects', ['code' => 'NPA-III']);
    }

    public function test_authorized_user_can_update_a_project_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($user)->put(route('projects.update', $project), [
            'code' => $project->code,
            'name' => 'Renamed Project',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Renamed Project', 'status' => 'active']);
    }

    public function test_authorized_user_can_delete_a_project_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->delete(route('projects.destroy', $project));

        $response->assertRedirect(route('projects.index'));
        $this->assertSoftDeleted($project);
    }
}
