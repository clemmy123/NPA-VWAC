<?php

namespace Tests\Unit;

use App\Models\Council;
use App\Models\District;
use App\Models\Division;
use App\Models\Kitongoji;
use App\Models\Region;
use App\Models\Township;
use App\Models\VillageMtaa;
use App\Models\Ward;
use App\Support\AdminLocationLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocationLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_path_to_level_walks_up_the_real_hierarchy_and_skips_township_for_ward(): void
    {
        $this->assertSame(
            ['region', 'district', 'council', 'division', 'ward'],
            AdminLocationLevel::pathToLevel('ward'),
        );

        $this->assertSame(
            ['region', 'district', 'council', 'division', 'township'],
            AdminLocationLevel::pathToLevel('township'),
        );

        $this->assertSame(
            ['region', 'district', 'council', 'division', 'ward', 'village_mtaa', 'kitongoji'],
            AdminLocationLevel::pathToLevel('kitongoji'),
        );

        $this->assertSame(['region'], AdminLocationLevel::pathToLevel('region'));
    }

    public function test_options_for_the_top_level_ignores_any_parent_id(): void
    {
        Region::factory()->create(['name' => 'Zanzibar']);
        Region::factory()->create(['name' => 'Arusha']);

        $options = AdminLocationLevel::options('region');

        $this->assertCount(2, $options);
        $this->assertSame(['Arusha', 'Zanzibar'], array_column($options, 'name'));
    }

    public function test_options_for_a_nested_level_are_filtered_by_parent_id(): void
    {
        $regionA = Region::factory()->create();
        $regionB = Region::factory()->create();
        District::factory()->create(['region_id' => $regionA->region_id, 'name' => 'In Region A']);
        District::factory()->create(['region_id' => $regionB->region_id, 'name' => 'In Region B']);

        $options = AdminLocationLevel::options('district', $regionA->region_id);

        $this->assertCount(1, $options);
        $this->assertSame('In Region A', $options[0]['name']);
    }

    public function test_options_for_a_nested_level_with_no_parent_id_returns_empty(): void
    {
        $this->assertSame([], AdminLocationLevel::options('district', null));
    }

    public function test_ward_options_are_filtered_by_division_not_township(): void
    {
        $division = Division::factory()->create();
        $township = Township::factory()->create(['division_id' => $division->division_id]);
        Ward::factory()->create(['division_id' => $division->division_id, 'township_id' => null, 'name' => 'Direct Ward']);
        Ward::factory()->create(['division_id' => $division->division_id, 'township_id' => $township->township_id, 'name' => 'Via Township Ward']);
        Ward::factory()->create(['name' => 'Elsewhere Ward']);

        $options = AdminLocationLevel::options('ward', $division->division_id);

        $this->assertCount(2, $options);
        $this->assertContains('Direct Ward', array_column($options, 'name'));
        $this->assertContains('Via Township Ward', array_column($options, 'name'));
    }

    public function test_ancestor_chain_resolves_every_level_up_to_the_root(): void
    {
        $region = Region::factory()->create(['name' => 'Dodoma']);
        $district = District::factory()->create(['region_id' => $region->region_id, 'name' => 'Dodoma Urban']);
        $council = Council::factory()->create(['district_id' => $district->district_id, 'name' => 'Dodoma City Council']);
        $division = Division::factory()->create(['council_id' => $council->council_id, 'name' => 'Central Division']);
        $ward = Ward::factory()->create(['division_id' => $division->division_id, 'name' => 'Central Ward']);
        $villageMtaa = VillageMtaa::factory()->create(['ward_id' => $ward->ward_id, 'name' => 'Central Mtaa']);
        $kitongoji = Kitongoji::factory()->create(['village_mtaa_id' => $villageMtaa->village_mtaa_id, 'name' => 'Kitongoji A']);

        $chain = AdminLocationLevel::ancestorChain('kitongoji', $kitongoji->kitongoji_id);

        $this->assertSame([
            'kitongoji' => ['id' => $kitongoji->kitongoji_id, 'name' => 'Kitongoji A'],
            'village_mtaa' => ['id' => $villageMtaa->village_mtaa_id, 'name' => 'Central Mtaa'],
            'ward' => ['id' => $ward->ward_id, 'name' => 'Central Ward'],
            'division' => ['id' => $division->division_id, 'name' => 'Central Division'],
            'council' => ['id' => $council->council_id, 'name' => 'Dodoma City Council'],
            'district' => ['id' => $district->district_id, 'name' => 'Dodoma Urban'],
            'region' => ['id' => $region->region_id, 'name' => 'Dodoma'],
        ], $chain);
    }

    public function test_ancestor_chain_for_a_missing_record_returns_empty(): void
    {
        $this->assertSame([], AdminLocationLevel::ancestorChain('region', 999999));
    }

    public function test_name_resolves_a_single_level_and_id(): void
    {
        $region = Region::factory()->create(['name' => 'Mbeya']);

        $this->assertSame('Mbeya', AdminLocationLevel::name('region', $region->region_id));
        $this->assertNull(AdminLocationLevel::name('region', 999999));
    }

    public function test_ward_options_can_be_listed_by_council_skipping_division(): void
    {
        $council = Council::factory()->create();
        $division = Division::factory()->create(['council_id' => $council->council_id]);
        Ward::factory()->create(['division_id' => $division->division_id, 'name' => 'Council Ward']);
        Ward::factory()->create(['name' => 'Elsewhere Ward']);

        $options = AdminLocationLevel::options('ward', $council->council_id, 'council');

        $this->assertCount(1, $options);
        $this->assertSame('Council Ward', $options[0]['name']);
    }
}
