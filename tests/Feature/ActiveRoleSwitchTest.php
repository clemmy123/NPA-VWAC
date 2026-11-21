<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActiveRoleSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRoles(string ...$roles): User
    {
        $user = User::factory()->create();
        $user->assignRole($roles);

        return $user;
    }

    public function test_user_with_several_roles_acts_as_the_highest_priority_role_until_they_switch(): void
    {
        $user = $this->userWithRoles('Data Entry User', 'Project Manager');

        $this->assertSame('Project Manager', $user->activeRoleName());
        $this->assertTrue($user->hasRole('Project Manager'));
        $this->assertFalse($user->hasRole('Data Entry User'));
    }

    public function test_switching_limits_roles_and_permissions_to_the_chosen_role(): void
    {
        $user = $this->userWithRoles('Project Manager', 'Data Entry User');
        $permission = $user->getAllPermissions()->pluck('name')
            ->diff(Role::findByName('Data Entry User')->permissions->pluck('name'))->first();
        $this->assertNotNull($permission, 'Project Manager should hold a permission Data Entry User lacks.');
        $this->assertTrue($user->can($permission));

        $this->actingAs($user)->post(route('active-role.update'), ['role' => 'Data Entry User'])
            ->assertRedirect(route('dashboard'));

        $user = $user->fresh();
        $this->assertSame('Data Entry User', $user->activeRoleName());
        $this->assertFalse($user->hasRole('Project Manager'));
        $this->assertFalse($user->can($permission));
    }

    public function test_user_cannot_switch_to_a_role_they_do_not_hold(): void
    {
        $user = $this->userWithRoles('Project Manager', 'Data Entry User');

        $this->actingAs($user)->post(route('active-role.update'), ['role' => 'Super Admin'])
            ->assertSessionHasErrors('role');

        $this->assertNotSame('Super Admin', $user->fresh()->activeRoleName());
    }

    public function test_single_role_users_are_unaffected_and_see_no_switcher(): void
    {
        $user = $this->userWithRoles('Thematic Manager');

        $this->assertSame('Thematic Manager', $user->activeRoleName());
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('role-switch-item', false);
    }

    public function test_switcher_lists_every_held_role_for_multi_role_users(): void
    {
        $user = $this->userWithRoles('Project Manager', 'Data Entry User');

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('role-switch-item', false)
            ->assertSee('Data Entry User');
    }
}
