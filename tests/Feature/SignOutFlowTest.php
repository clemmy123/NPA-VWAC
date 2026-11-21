<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignOutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_login_page_no_longer_exists(): void
    {
        $this->get('/local-login')->assertNotFound();
        $this->post('/local-login')->assertNotFound();
    }

    public function test_signing_out_ends_the_central_jumuishi_session(): void
    {
        config(['jumuishi.url' => 'https://jumuishi.example']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect('https://jumuishi.example/central-logout');
        $this->assertGuest();
    }

    public function test_signing_out_on_a_developer_machine_without_a_hub_shows_the_dev_picker(): void
    {
        config(['jumuishi.url' => 'http://localhost']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('dev-login'));
    }

    public function test_signed_in_users_are_bounced_away_from_the_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('login'))->assertRedirect();
    }

    public function test_signed_out_users_cannot_reach_app_pages_and_pages_are_not_cached(): void
    {
        $this->get(route('dashboard'))->assertRedirect();

        $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
