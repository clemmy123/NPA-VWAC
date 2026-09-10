<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_user_can_log_in_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'auth_provider' => 'local',
            'password_login_enabled' => true,
            'status' => 'active',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post(route('local-login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_local_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create([
            'auth_provider' => 'local',
            'password_login_enabled' => true,
            'status' => 'active',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post(route('local-login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_jumuishi_user_cannot_log_in_via_local_login_even_with_a_password_set(): void
    {
        $user = User::factory()->create([
            'auth_provider' => 'jumuishi',
            'password_login_enabled' => false,
            'status' => 'active',
            'password' => Hash::make('some-password'),
        ]);

        $response = $this->post(route('local-login.store'), [
            'email' => $user->email,
            'password' => 'some-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_local_user_can_change_their_password(): void
    {
        $user = User::factory()->create([
            'auth_provider' => 'local',
            'password_login_enabled' => true,
            'status' => 'active',
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)->put(route('local-password.update'), [
            'current_password' => 'old-password',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
    }
}
