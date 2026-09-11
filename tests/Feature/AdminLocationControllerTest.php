<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_it_returns_top_level_options_for_region(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        Region::factory()->create(['name' => 'Tanga']);

        $response = $this->actingAs($user)->getJson('/admin-locations/region');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Tanga']);
    }

    public function test_it_filters_by_parent_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        $regionA = Region::factory()->create();
        $regionB = Region::factory()->create();
        District::factory()->create(['region_id' => $regionA->region_id, 'name' => 'Correct District']);
        District::factory()->create(['region_id' => $regionB->region_id, 'name' => 'Wrong District']);

        $response = $this->actingAs($user)->getJson("/admin-locations/district?parent_id={$regionA->region_id}");

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Correct District']);
    }

    public function test_it_404s_for_an_invalid_level(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');

        $response = $this->actingAs($user)->getJson('/admin-locations/planet');

        $response->assertNotFound();
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->getJson('/admin-locations/region');

        $response->assertUnauthorized();
    }
}
