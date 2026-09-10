<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    public function test_unauthorized_user_is_forbidden_from_the_users_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Project Manager');

        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('users.create'))->assertForbidden();
    }

    public function test_super_admin_can_create_a_jumuishi_sso_user(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Grace Mushi',
            'email' => 'grace.mushi@example.test',
            'auth_provider' => 'jumuishi',
            'status' => 'active',
            'role' => 'Project Manager',
        ]);

        $response->assertRedirect(route('users.index'));
        $user = User::where('email', 'grace.mushi@example.test')->firstOrFail();
        $this->assertSame('jumuishi', $user->auth_provider);
        $this->assertFalse($user->password_login_enabled);
        $this->assertTrue($user->hasRole('Project Manager'));
    }

    public function test_super_admin_can_create_a_local_organization_user(): void
    {
        $admin = $this->superAdmin();
        $organization = Organization::factory()->create(['name' => 'CRDB Bank']);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Bank Reporter',
            'email' => 'reporter@crdb.example.test',
            'organization_id' => $organization->id,
            'auth_provider' => 'local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'role' => 'Data Entry User',
        ]);

        $response->assertRedirect(route('users.index'));
        $user = User::where('email', 'reporter@crdb.example.test')->firstOrFail();
        $this->assertSame('local', $user->auth_provider);
        $this->assertTrue($user->password_login_enabled);
        $this->assertSame($organization->id, $user->organization_id);
        $this->assertTrue($user->hasRole('Data Entry User'));
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_creating_a_local_user_without_a_password_fails_validation(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'No Password',
            'email' => 'nopassword@example.test',
            'auth_provider' => 'local',
            'status' => 'active',
            'role' => 'Data Entry User',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_super_admin_can_deactivate_and_reactivate_a_user(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create(['status' => 'active']);

        $this->actingAs($admin)->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'));
        $this->assertSame('inactive', $target->fresh()->status);

        $this->actingAs($admin)->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'));
        $this->assertSame('active', $target->fresh()->status);
    }
}
