<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_switch_the_ui_to_swahili_without_translating_plan_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $project = Project::factory()->create(['name' => 'NPA-VAWC (2026-2030)']);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('locale.switch', 'sw'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame('sw', session('locale'));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('lang="sw"', false);
        $response->assertSee('>Dashibodi</h4>', false);
        $response->assertSee('>Nyumbani</a>', false);
        $response->assertSee('Ukusanyaji wa Data');
        $response->assertSee($project->name);
        $response->assertSee('>SW</span>', false);
        $response->assertDontSee('>Dashboard</h4>', false);
    }

    public function test_user_can_switch_back_to_english(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Thematic Manager');

        $this->actingAs($user)->withSession(['locale' => 'sw'])
            ->from(route('dashboard'))
            ->get(route('locale.switch', 'en'))
            ->assertRedirect(route('dashboard'));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('lang="en"', false);
        $response->assertSee('>Dashboard</h4>', false);
        $response->assertSee('>Home</a>', false);
    }

    public function test_language_dropdown_defaults_to_english(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Thematic Manager');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('lang="en"', false);
        $response->assertSee('lang-dropdown', false);
        $response->assertSee('>EN</span>', false);
        $response->assertSee('hreflang="en"', false);
        $response->assertSee('hreflang="sw"', false);
    }

    public function test_unknown_locale_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Thematic Manager');

        $this->actingAs($user)
            ->get(route('locale.switch', 'fr'))
            ->assertNotFound();
    }
}
