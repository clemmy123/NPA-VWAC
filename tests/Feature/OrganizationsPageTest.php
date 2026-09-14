<?php

namespace Tests\Feature;

use App\Models\Council;
use App\Models\Division;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Region;
use App\Models\User;
use App\Models\VillageMtaa;
use App\Models\Ward;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationsPageTest extends TestCase
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

    public function test_create_page_starts_with_only_the_region_dropdown_visible(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->get(route('organizations.create'))
            ->assertOk()
            ->assertSee('Region')
            ->getContent();

        $this->assertStringContainsString('data-level="region"', $html);
        $this->assertDoesNotMatchRegularExpression('/org-loc-field" data-level="region" hidden/', $html);
        $this->assertMatchesRegularExpression('/org-loc-field" data-level="district" hidden/', $html);
        $this->assertMatchesRegularExpression('/org-loc-field" data-level="council" hidden/', $html);
        $this->assertMatchesRegularExpression('/org-loc-field" data-level="ward" hidden/', $html);
        $this->assertMatchesRegularExpression('/org-loc-field" data-level="village_mtaa" hidden/', $html);
    }

    public function test_it_stores_an_organization_with_a_village_location(): void
    {
        $type = OrganizationType::factory()->create();
        $village = VillageMtaa::factory()->create(['name' => 'Kivukoni']);

        $this->actingAs($this->superAdmin())
            ->post(route('organizations.store'), [
                'name' => 'CRDB Bank',
                'organization_type_id' => $type->id,
                'location_level' => 'village_mtaa',
                'location_id' => $village->village_mtaa_id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('organizations.index'));

        $this->assertDatabaseHas('organizations', [
            'name' => 'CRDB Bank',
            'location_level' => 'village_mtaa',
            'location_id' => $village->village_mtaa_id,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertSee('Kivukoni');
    }

    public function test_it_rejects_an_invalid_location(): void
    {
        $this->actingAs($this->superAdmin())
            ->from(route('organizations.create'))
            ->post(route('organizations.store'), [
                'name' => 'Invalid Location Org',
                'location_level' => 'region',
                'location_id' => 999999,
                'is_active' => '1',
            ])
            ->assertRedirect(route('organizations.create'))
            ->assertSessionHasErrors('location_id');
    }

    public function test_edit_page_prefills_the_saved_location(): void
    {
        $region = Region::factory()->create(['name' => 'Dar es Salaam']);
        $organization = Organization::factory()->create([
            'location_level' => 'region',
            'location_id' => $region->region_id,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('organizations.edit', $organization))
            ->assertOk()
            ->assertSee('Dar es Salaam');
    }

    public function test_ward_options_can_be_filtered_by_council(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $council = Council::factory()->create();
        $division = Division::factory()->create(['council_id' => $council->council_id]);
        Ward::factory()->create(['division_id' => $division->division_id, 'name' => 'In Council Ward']);
        Ward::factory()->create(['name' => 'Other Ward']);

        $response = $this->actingAs($user)->getJson(
            "/admin-locations/ward?parent_id={$council->council_id}&parent_level=council"
        );

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'In Council Ward']);
        $response->assertJsonMissing(['name' => 'Other Ward']);
    }
}
