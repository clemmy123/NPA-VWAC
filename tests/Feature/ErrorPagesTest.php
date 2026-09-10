<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_unknown_route_renders_the_branded_404_page(): void
    {
        $response = $this->get('/this-route-does-not-exist');

        $response->assertNotFound();
        $response->assertSee('Page not found');
        $response->assertSee('status-card', false);
    }

    public function test_unauthorized_permission_renders_the_branded_403_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');

        $response = $this->actingAs($user)->get(route('projects.create'));

        $response->assertForbidden();
        $response->assertSee('Access denied');
        $response->assertSee('status-card', false);
    }
}
