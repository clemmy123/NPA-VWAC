<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThematicAreasPageTest extends TestCase
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

    public function test_authorized_user_sees_the_thematic_areas_table_page(): void
    {
        $user = $this->userWithRole('Super Admin');
        $thematicArea = ThematicArea::factory()->create(['name' => 'Child Protection']);

        $response = $this->actingAs($user)->get(route('thematic-areas.index'));

        $response->assertOk();
        $response->assertSee('table-card', false);
        $response->assertSee($thematicArea->name);
        $response->assertSee(route('thematic-areas.create'), false);
    }

    public function test_unauthorized_user_is_forbidden_from_the_thematic_areas_page(): void
    {
        $user = $this->userWithRole('Data Entry User');

        $response = $this->actingAs($user)->get(route('thematic-areas.index'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_create_a_thematic_area_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->post(route('thematic-areas.store'), [
            'project_id' => $project->id,
            'name' => 'Economic Empowerment',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('thematic-areas.index'));
        $this->assertDatabaseHas('thematic_areas', ['name' => 'Economic Empowerment']);
    }

    public function test_authorized_user_can_update_a_thematic_area_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $thematicArea = ThematicArea::factory()->create();

        $response = $this->actingAs($user)->put(route('thematic-areas.update', $thematicArea), [
            'project_id' => $thematicArea->project_id,
            'name' => 'Renamed Area',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('thematic-areas.index'));
        $this->assertDatabaseHas('thematic_areas', ['id' => $thematicArea->id, 'name' => 'Renamed Area']);
    }

    public function test_authorized_user_can_delete_a_thematic_area_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $thematicArea = ThematicArea::factory()->create();

        $response = $this->actingAs($user)->delete(route('thematic-areas.destroy', $thematicArea));

        $response->assertRedirect(route('thematic-areas.index'));
        $this->assertSoftDeleted($thematicArea);
    }
}
