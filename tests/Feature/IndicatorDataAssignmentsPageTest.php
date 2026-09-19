<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
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

    public function test_data_assignment_workflow_is_available_to_authorized_managers(): void
    {
        $this->assertTrue(Route::has('indicator-data-assignments.index'));
        $this->assertTrue(Route::has('indicator-data-assignments.store'));
        $this->assertTrue(Permission::where('name', 'indicator.assign-user')->exists());
    }

    public function test_blank_location_saves_as_all_locations(): void
    {
        $manager = $this->userWithRole('Thematic Manager');
        $indicator = Indicator::factory()->create();
        $reporter = $this->userWithRole('Data Entry User');

        $this->actingAs($manager)->post(route('indicator-data-assignments.store'), [
            'indicator_id' => $indicator->id,
            'user_id' => $reporter->id,
            'location_level' => '',
            'location_id' => '',
        ])->assertRedirect(route('indicator-data-assignments.index'));

        $this->assertDatabaseHas('indicator_data_assignments', [
            'indicator_id' => $indicator->id,
            'user_id' => $reporter->id,
            'location_level' => null,
            'location_id' => null,
        ]);
    }

    public function test_location_level_without_a_place_is_rejected(): void
    {
        $manager = $this->userWithRole('Thematic Manager');
        $indicator = Indicator::factory()->create();
        $reporter = $this->userWithRole('Data Entry User');

        $this->actingAs($manager)->from(route('indicators.show', $indicator))->post(route('indicator-data-assignments.store'), [
            'indicator_id' => $indicator->id,
            'user_id' => $reporter->id,
            'location_level' => 'region',
            'location_id' => '',
            'redirect_to' => route('indicators.show', $indicator),
        ])->assertRedirect(route('indicators.show', $indicator))->assertSessionHasErrors('location_id');
    }

    public function test_assignment_keeps_a_picked_location(): void
    {
        $manager = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create();
        $reporter = $this->userWithRole('Data Entry User');
        $region = Region::factory()->create(['name' => 'Mwanza']);

        $this->actingAs($manager)->post(route('indicator-data-assignments.store'), [
            'indicator_id' => $indicator->id,
            'user_id' => $reporter->id,
            'location_level' => 'region',
            'location_id' => $region->region_id,
            'redirect_to' => route('indicators.show', $indicator),
        ])->assertRedirect(route('indicators.show', $indicator));

        $this->assertDatabaseHas('indicator_data_assignments', [
            'indicator_id' => $indicator->id,
            'user_id' => $reporter->id,
            'location_level' => 'region',
            'location_id' => $region->region_id,
        ]);

        $this->actingAs($manager)->get(route('indicators.show', $indicator))
            ->assertOk()
            ->assertSee('Region:')
            ->assertSee('Mwanza');
    }

    public function test_thematic_manager_assignment_form_lists_only_data_entry_users(): void
    {
        $manager = $this->userWithRole('Thematic Manager');
        $dataEntry = $this->userWithRole('Data Entry User');
        $superAdmin = $this->userWithRole('Super Admin');
        $projectManager = $this->userWithRole('Project Manager');
        $indicator = Indicator::factory()->create();

        $create = $this->actingAs($manager)->get(route('indicator-data-assignments.create'));
        $create->assertOk();
        $create->assertSee($dataEntry->email);
        $create->assertDontSee($superAdmin->email);
        $create->assertDontSee($projectManager->email);
        $create->assertDontSee($manager->email);

        $show = $this->actingAs($manager)->get(route('indicators.show', $indicator));
        $show->assertOk();
        $show->assertSee($dataEntry->email);
        $show->assertDontSee($superAdmin->email);
        $show->assertDontSee($projectManager->email);
    }

    public function test_thematic_manager_cannot_assign_a_non_data_entry_user(): void
    {
        $manager = $this->userWithRole('Thematic Manager');
        $superAdmin = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create();

        $this->actingAs($manager)->from(route('indicators.show', $indicator))->post(route('indicator-data-assignments.store'), [
            'indicator_id' => $indicator->id,
            'user_id' => $superAdmin->id,
            'redirect_to' => route('indicators.show', $indicator),
        ])->assertRedirect(route('indicators.show', $indicator))->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('indicator_data_assignments', [
            'indicator_id' => $indicator->id,
            'user_id' => $superAdmin->id,
        ]);
    }
}
