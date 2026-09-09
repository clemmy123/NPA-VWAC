<?php

namespace Tests\Feature;

use App\Models\Council;
use App\Models\DataSource;
use App\Models\Dimension;
use App\Models\DimensionOption;
use App\Models\District;
use App\Models\Division;
use App\Models\FinancialYear;
use App\Models\FundSource;
use App\Models\Kitongoji;
use App\Models\MeasurementType;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Region;
use App\Models\ReportingPeriod;
use App\Models\Township;
use App\Models\UnitOfMeasure;
use App\Models\VillageMtaa;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceDataRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ward_nests_up_through_division_council_district_region(): void
    {
        $ward = Ward::factory()->create();

        $division = $ward->division;
        $this->assertInstanceOf(Division::class, $division);

        $council = $division->council;
        $this->assertInstanceOf(Council::class, $council);

        $district = $council->district;
        $this->assertInstanceOf(District::class, $district);

        $region = $district->region;
        $this->assertInstanceOf(Region::class, $region);
    }

    public function test_ward_has_no_township_by_default(): void
    {
        $ward = Ward::factory()->create();

        $this->assertNull($ward->township_id);
        $this->assertNull($ward->township);
    }

    public function test_ward_with_township_shares_the_townships_division(): void
    {
        $ward = Ward::factory()->withTownship()->create();

        $this->assertInstanceOf(Township::class, $ward->township);
        $this->assertSame($ward->division_id, $ward->township->division_id);
    }

    public function test_village_mtaa_and_kitongoji_nest_under_ward(): void
    {
        $ward = Ward::factory()->create();
        $village = VillageMtaa::factory()->create(['ward_id' => $ward->ward_id]);
        $kitongoji = Kitongoji::factory()->create(['village_mtaa_id' => $village->village_mtaa_id]);

        $this->assertSame($ward->ward_id, $village->ward->ward_id);
        $this->assertSame($village->village_mtaa_id, $kitongoji->villageMtaa->village_mtaa_id);
    }

    public function test_reporting_period_requires_and_belongs_to_a_financial_year(): void
    {
        $financialYear = FinancialYear::factory()->create();
        $period = ReportingPeriod::factory()->create(['financial_year_id' => $financialYear->id]);

        $this->assertInstanceOf(FinancialYear::class, $period->financialYear);
        $this->assertSame($financialYear->id, $period->financialYear->id);
        $this->assertContains($period->id, $financialYear->reportingPeriods->pluck('id'));
    }

    public function test_dimension_option_requires_a_dimension(): void
    {
        $dimension = Dimension::factory()->create();
        $option = DimensionOption::factory()->create(['dimension_id' => $dimension->id]);

        $this->assertInstanceOf(Dimension::class, $option->dimension);
        $this->assertContains($option->id, $dimension->options->pluck('id'));
    }

    public function test_organization_belongs_to_an_organization_type(): void
    {
        $type = OrganizationType::factory()->create();
        $organization = Organization::factory()->create(['organization_type_id' => $type->id]);

        $this->assertSame($type->id, $organization->organization_type_id);
    }

    public function test_lookup_factories_create_valid_rows(): void
    {
        $this->assertNotNull(MeasurementType::factory()->create()->id);
        $this->assertNotNull(UnitOfMeasure::factory()->create()->id);
        $this->assertNotNull(DataSource::factory()->create()->id);
        $this->assertNotNull(FundSource::factory()->create()->id);
    }
}
