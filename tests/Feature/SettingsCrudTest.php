<?php

namespace Tests\Feature;

use App\Models\Dimension;
use App\Models\DimensionOption;
use App\Models\FinancialYear;
use App\Models\MeasurementType;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\ReportingPeriod;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsCrudTest extends TestCase
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

    public function test_unauthorized_user_is_forbidden_from_the_settings_hub(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Project Manager');

        $this->actingAs($user)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($user)->get(route('organizations.create'))->assertForbidden();
    }

    public function test_super_admin_sees_the_settings_hub(): void
    {
        $this->actingAs($this->superAdmin())->get(route('settings.index'))->assertOk();
    }

    public function test_organization_types_crud(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('organization-types.store'), ['code' => 'bank', 'name' => 'Bank'])
            ->assertRedirect(route('organization-types.index'));
        $type = OrganizationType::where('code', 'bank')->firstOrFail();

        $this->actingAs($admin)->put(route('organization-types.update', $type), ['code' => 'bank', 'name' => 'Bank Updated'])
            ->assertRedirect(route('organization-types.index'));
        $this->assertDatabaseHas('organization_types', ['id' => $type->id, 'name' => 'Bank Updated']);

        $this->actingAs($admin)->delete(route('organization-types.destroy', $type))
            ->assertRedirect(route('organization-types.index'));
        $this->assertDatabaseMissing('organization_types', ['id' => $type->id]);
    }

    public function test_organizations_crud(): void
    {
        $admin = $this->superAdmin();
        $type = OrganizationType::factory()->create();

        $this->actingAs($admin)->post(route('organizations.store'), [
            'name' => 'CRDB Bank', 'organization_type_id' => $type->id,
        ])->assertRedirect(route('organizations.index'));
        $organization = Organization::where('name', 'CRDB Bank')->firstOrFail();

        $this->actingAs($admin)->put(route('organizations.update', $organization), [
            'name' => 'CRDB Bank PLC', 'organization_type_id' => $type->id,
        ])->assertRedirect(route('organizations.index'));
        $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'name' => 'CRDB Bank PLC']);

        $this->actingAs($admin)->delete(route('organizations.destroy', $organization))
            ->assertRedirect(route('organizations.index'));
        $this->assertDatabaseMissing('organizations', ['id' => $organization->id]);
    }

    public function test_financial_years_crud_and_single_current_year_enforcement(): void
    {
        $admin = $this->superAdmin();
        $existingCurrent = FinancialYear::factory()->create(['is_current' => true]);

        $this->actingAs($admin)->post(route('financial-years.store'), [
            'name' => '2030/31', 'start_date' => '2030-07-01', 'end_date' => '2031-06-30', 'is_current' => '1',
        ])->assertRedirect(route('financial-years.index'));

        $financialYear = FinancialYear::where('name', '2030/31')->firstOrFail();
        $this->assertTrue($financialYear->fresh()->is_current);
        $this->assertFalse($existingCurrent->fresh()->is_current);

        $this->actingAs($admin)->delete(route('financial-years.destroy', $financialYear))
            ->assertRedirect(route('financial-years.index'));
        $this->assertDatabaseMissing('financial_years', ['id' => $financialYear->id]);
    }

    public function test_reporting_periods_crud(): void
    {
        $admin = $this->superAdmin();
        $financialYear = FinancialYear::factory()->create();

        $this->actingAs($admin)->post(route('reporting-periods.store'), [
            'financial_year_id' => $financialYear->id, 'code' => 'Q1', 'name' => 'Quarter 1',
            'period_type' => 'quarter', 'sequence' => 1, 'start_date' => '2026-07-01', 'end_date' => '2026-09-30',
        ])->assertRedirect(route('reporting-periods.index'));

        $period = ReportingPeriod::where('code', 'Q1')->where('financial_year_id', $financialYear->id)->firstOrFail();

        $this->actingAs($admin)->delete(route('reporting-periods.destroy', $period))
            ->assertRedirect(route('reporting-periods.index'));
        $this->assertDatabaseMissing('reporting_periods', ['id' => $period->id]);
    }

    public function test_measurement_types_crud(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('measurement-types.store'), ['code' => 'index_2', 'name' => 'Index'])
            ->assertRedirect(route('measurement-types.index'));

        $measurementType = MeasurementType::where('code', 'index_2')->firstOrFail();

        $this->actingAs($admin)->delete(route('measurement-types.destroy', $measurementType))
            ->assertRedirect(route('measurement-types.index'));
        $this->assertDatabaseMissing('measurement_types', ['id' => $measurementType->id]);
    }

    public function test_units_of_measure_crud(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('units-of-measure.store'), ['code' => 'schools_2', 'name' => 'Schools'])
            ->assertRedirect(route('units-of-measure.index'));

        $unit = UnitOfMeasure::where('code', 'schools_2')->firstOrFail();

        $this->actingAs($admin)->delete(route('units-of-measure.destroy', $unit))
            ->assertRedirect(route('units-of-measure.index'));
        $this->assertDatabaseMissing('units_of_measure', ['id' => $unit->id]);
    }

    public function test_dimensions_and_options_crud(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('dimensions.store'), ['code' => 'region_type', 'name' => 'Region Type'])
            ->assertRedirect(route('dimensions.index'));
        $dimension = Dimension::where('code', 'region_type')->firstOrFail();

        $this->actingAs($admin)->post(route('dimensions.options.store', $dimension), ['code' => 'urban', 'name' => 'Urban'])
            ->assertRedirect(route('dimensions.edit', $dimension));
        $option = DimensionOption::where('dimension_id', $dimension->id)->where('code', 'urban')->firstOrFail();

        $this->actingAs($admin)->put(route('dimensions.options.update', [$dimension, $option]), ['code' => 'urban', 'name' => 'Urban Area'])
            ->assertRedirect(route('dimensions.edit', $dimension));
        $this->assertDatabaseHas('dimension_options', ['id' => $option->id, 'name' => 'Urban Area']);

        $this->actingAs($admin)->delete(route('dimensions.options.destroy', [$dimension, $option]))
            ->assertRedirect(route('dimensions.edit', $dimension));
        $this->assertDatabaseMissing('dimension_options', ['id' => $option->id]);

        $this->actingAs($admin)->delete(route('dimensions.destroy', $dimension))
            ->assertRedirect(route('dimensions.index'));
        $this->assertDatabaseMissing('dimensions', ['id' => $dimension->id]);
    }
}
