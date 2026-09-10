<?php

namespace Tests\Feature;

use App\Models\Intervention;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterventionsPageTest extends TestCase
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

    public function test_authorized_user_sees_the_interventions_table_page(): void
    {
        $user = $this->userWithRole('Super Admin');
        $intervention = Intervention::factory()->create(['name' => 'Community Dialogues']);

        $response = $this->actingAs($user)->get(route('interventions.index'));

        $response->assertOk();
        $response->assertSee('table-card', false);
        $response->assertSee($intervention->name);
        $response->assertSee(route('interventions.create'), false);
    }

    public function test_unauthorized_user_is_forbidden_from_the_interventions_create_page(): void
    {
        $user = $this->userWithRole('Project Manager');

        $response = $this->actingAs($user)->get(route('interventions.create'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_create_an_intervention_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $thematicArea = ThematicArea::factory()->create();

        $response = $this->actingAs($user)->post(route('interventions.store'), [
            'thematic_area_id' => $thematicArea->id,
            'name' => 'School Outreach',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('interventions.index'));
        $this->assertDatabaseHas('interventions', ['name' => 'School Outreach']);
    }

    public function test_authorized_user_can_update_an_intervention_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $intervention = Intervention::factory()->create();

        $response = $this->actingAs($user)->put(route('interventions.update', $intervention), [
            'thematic_area_id' => $intervention->thematic_area_id,
            'name' => 'Renamed Intervention',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('interventions.index'));
        $this->assertDatabaseHas('interventions', ['id' => $intervention->id, 'name' => 'Renamed Intervention']);
    }

    public function test_authorized_user_can_delete_an_intervention_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $intervention = Intervention::factory()->create();

        $response = $this->actingAs($user)->delete(route('interventions.destroy', $intervention));

        $response->assertRedirect(route('interventions.index'));
        $this->assertSoftDeleted($intervention);
    }
}
