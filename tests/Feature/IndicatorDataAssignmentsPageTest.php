<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorDataAssignmentsPageTest extends TestCase
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

    public function test_unauthorized_user_is_forbidden_from_the_assignments_page(): void
    {
        $user = $this->userWithRole('Project Manager');

        $this->actingAs($user)->get(route('indicator-data-assignments.index'))->assertForbidden();
    }

    public function test_thematic_manager_can_create_an_assignment(): void
    {
        $manager = $this->userWithRole('Thematic Manager');
        $indicator = Indicator::factory()->create();
        $reporter = $this->userWithRole('Data Entry User');

        $response = $this->actingAs($manager)->post(route('indicator-data-assignments.store'), [
            'indicator_id' => $indicator->id,
            'user_id' => $reporter->id,
        ]);

        $response->assertRedirect(route('indicator-data-assignments.index'));
        $this->assertDatabaseHas('indicator_data_assignments', [
            'indicator_id' => $indicator->id,
            'user_id' => $reporter->id,
            'is_active' => true,
        ]);
    }

    public function test_thematic_manager_can_update_and_delete_an_assignment(): void
    {
        $manager = $this->userWithRole('Thematic Manager');
        $assignment = IndicatorDataAssignment::factory()->create();

        $this->actingAs($manager)->put(route('indicator-data-assignments.update', $assignment), [
            'indicator_id' => $assignment->indicator_id,
            'user_id' => $assignment->user_id,
            'is_active' => '0',
        ])->assertRedirect(route('indicator-data-assignments.index'));
        $this->assertFalse($assignment->fresh()->is_active);

        $this->actingAs($manager)->delete(route('indicator-data-assignments.destroy', $assignment))
            ->assertRedirect(route('indicator-data-assignments.index'));
        $this->assertDatabaseMissing('indicator_data_assignments', ['id' => $assignment->id]);
    }
}
