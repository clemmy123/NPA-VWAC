<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function localUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'auth_provider' => 'local',
            'password_login_enabled' => true,
            'status' => 'active',
            'password' => Hash::make('old-password'),
        ], $overrides));
    }

    public function test_a_local_user_with_an_unexpired_password_browses_normally(): void
    {
        $user = $this->localUser(['password_changed_at' => now()->subDays(10)]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_a_local_user_whose_password_is_older_than_90_days_is_redirected(): void
    {
        $user = $this->localUser(['password_changed_at' => now()->subDays(91)]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('local-password.edit'));
    }

    public function test_a_jumuishi_user_is_never_forced_to_change_a_password(): void
    {
        $user = User::factory()->create([
            'auth_provider' => 'jumuishi',
            'password_login_enabled' => false,
            'status' => 'active',
            'force_password_change' => true,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_a_super_admin_can_force_a_local_users_password_change_without_waiting_90_days(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $target = $this->localUser(['password_changed_at' => now()]);

        $this->actingAs($admin)
            ->post(route('users.force-password-change', $target))
            ->assertRedirect(route('users.index'));

        $this->assertTrue($target->fresh()->force_password_change);
    }

    public function test_a_local_user_flagged_by_an_admin_is_redirected_regardless_of_password_age(): void
    {
        $target = $this->localUser([
            'password_changed_at' => now(),
            'force_password_change' => true,
        ]);

        $this->actingAs($target)->get(route('dashboard'))->assertRedirect(route('local-password.edit'));
    }

    public function test_changing_the_password_clears_the_forced_flag(): void
    {
        $user = $this->localUser([
            'force_password_change' => true,
            'password_changed_at' => now(),
        ]);

        $this->actingAs($user)->put(route('local-password.update'), [
            'current_password' => 'old-password',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->force_password_change);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }
}
