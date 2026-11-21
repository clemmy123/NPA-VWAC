<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalAuthTest extends TestCase
{
    use RefreshDatabase;

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
