<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardShellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_sees_the_full_shell_and_nav_on_the_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('JAMII FUATILIA | Dashboard', false);
        $response->assertSee('>JAMII FUATILIA</span>', false);
        $response->assertDontSee('Laravel |', false);
        $response->assertSee('left-side-menu', false);
        $response->assertSee('sidebar-home', false);
        $response->assertSee('>Home</a>', false);
        $response->assertSee('bi-grid', false);
        $response->assertDontSee('bi-speedometer2', false);
        $response->assertSee('navbar-custom', false);
        $response->assertSee(route('projects.index'), false);
        $response->assertSee('Data Collections');
        $response->assertSee(route('indicator-data-entries.index'), false);
        $response->assertSee('General Report');
        $response->assertSee('Workstation Reports');
        $response->assertSee(route('reports.general'), false);
        $response->assertSee(route('reports.workstation'), false);
    }

    public function test_data_entry_user_only_sees_nav_items_they_are_permitted_to_view(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Data Collections');
        $response->assertSee(route('indicator-data-entries.index'), false);
        $response->assertDontSee('General Report');
        $response->assertDontSee('Workstation Reports');
        $response->assertDontSee(route('projects.index'), false);
    }
}
