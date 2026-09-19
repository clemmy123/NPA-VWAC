<?php

namespace Tests\Feature;

use App\Models\Council;
use App\Models\Division;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\MeasurementType;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Region;
use App\Models\ReportingPeriod;
use App\Models\User;
use App\Models\VillageMtaa;
use App\Models\Ward;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataEntryAssignmentScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_a_data_entry_user_sees_only_assigned_active_indicators(): void
    {
        $assignedIndicator = Indicator::factory()->create(['status' => 'active']);
        $otherIndicator = Indicator::factory()->create(['status' => 'active']);

        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $assignedIndicator->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson('/indicators');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($assignedIndicator->id, $ids);
        $this->assertNotContains($otherIndicator->id, $ids);
    }

    public function test_a_data_entry_user_sees_indicators_assigned_to_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $indicator = Indicator::factory()->create(['status' => 'active']);

        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => User::factory()->create()->id,
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($user)->getJson('/indicators');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($indicator->id, $ids);
    }

    public function test_a_data_entry_user_cannot_view_an_active_indicator_without_assignment(): void
    {
        $indicator = Indicator::factory()->create(['status' => 'active']);

        $user = User::factory()->create();
        $user->assignRole('Data Entry User');

        $this->actingAs($user)->getJson("/indicators/{$indicator->id}")->assertForbidden();
    }

    public function test_the_new_entry_form_lists_only_assigned_active_indicators(): void
    {
        $assignedIndicator = Indicator::factory()->create(['name' => 'Assigned Indicator', 'status' => 'active']);
        $otherIndicator = Indicator::factory()->create(['name' => 'Someone Elses Indicator', 'status' => 'active']);

        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $assignedIndicator->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/indicator-data-entries/create');

        $response->assertOk();
        $response->assertSee('Assigned Indicator');
        $response->assertDontSee('Someone Elses Indicator');
    }

    public function test_collection_form_hides_projects_without_collectable_indicators(): void
    {
        $indicator = Indicator::factory()->create(['status' => 'active']);
        $projectWithoutIndicator = Project::factory()->create(['name' => 'Visible planning project']);
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        $user->projects()->attach($projectWithoutIndicator->id, ['is_active' => true]);
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/indicator-data-entries/create');

        $response->assertOk();
        $response->assertDontSee('Visible planning project');
    }

    public function test_a_thematic_manager_sees_every_indicator_without_needing_an_assignment(): void
    {
        $indicator = Indicator::factory()->create(['status' => 'active']);

        $user = User::factory()->create();
        $user->assignRole('Thematic Manager');

        $response = $this->actingAs($user)->getJson('/indicators');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($indicator->id, $ids);
    }

    public function test_a_data_entry_user_cannot_submit_an_entry_for_a_future_reporting_period(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $futurePeriod = ReportingPeriod::factory()->create([
            'financial_year_id' => $financialYear->id,
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(4),
        ]);

        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'reporting_period_id' => $futurePeriod->id,
            'entry_date' => now()->toDateString(),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['reporting_period_id']);
    }

    public function test_a_super_admin_can_submit_an_entry_for_a_future_reporting_period(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $futurePeriod = ReportingPeriod::factory()->create([
            'financial_year_id' => $financialYear->id,
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(4),
        ]);

        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $response = $this->actingAs($user)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'reporting_period_id' => $futurePeriod->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 0,
        ]);

        $response->assertCreated();
    }

    public function test_collection_organization_is_taken_from_the_user_profile(): void
    {
        $assignedOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $indicator = Indicator::factory()->create(['collection_scope' => 'institutional', 'status' => 'active']);
        $financialYear = FinancialYear::factory()->started()->create();
        $user = User::factory()->create(['organization_id' => $assignedOrganization->id]);
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => User::factory()->create()->id,
            'organization_id' => $assignedOrganization->id,
        ]);

        $this->actingAs($user)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'organization_id' => $otherOrganization->id,
            'actual_value' => 0,
        ])->assertCreated();

        $this->assertDatabaseHas('indicator_data_entries', [
            'indicator_id' => $indicator->id,
            'organization_id' => $assignedOrganization->id,
        ]);

        $this->actingAs($user)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'organization_id' => $assignedOrganization->id,
            'actual_value' => 0,
        ])->assertCreated();
    }

    public function test_indicator_reporting_level_is_derived_and_the_location_must_match_it(): void
    {
        $indicator = Indicator::factory()->create([
            'requires_location' => true,
            'reporting_location_level' => 'council',
            'status' => 'active',
        ]);
        $financialYear = FinancialYear::factory()->started()->create();
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'location_level' => 'region',
            'location_id' => Region::factory()->create()->region_id,
        ])->assertJsonValidationErrors('location_id');
    }

    public function test_a_council_scoped_user_can_submit_a_street_level_indicator_within_their_council(): void
    {
        $council = Council::factory()->create();
        $division = Division::factory()->create(['council_id' => $council->council_id]);
        $ward = Ward::factory()->create(['division_id' => $division->division_id]);
        $street = VillageMtaa::factory()->create(['ward_id' => $ward->ward_id, 'type' => 'mtaa']);
        $indicator = Indicator::factory()->create([
            'requires_location' => true,
            'reporting_location_level' => 'village_mtaa',
            'status' => 'active',
        ]);
        $financialYear = FinancialYear::factory()->started()->create();
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
            'location_level' => 'council',
            'location_id' => $council->council_id,
        ]);

        $this->actingAs($user)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'location_level' => 'village_mtaa',
            'location_id' => $street->village_mtaa_id,
            'actual_value' => 12,
        ])->assertCreated();
    }

    public function test_financial_years_are_not_shown_on_the_indicator_first_collection_form(): void
    {
        $indicator = Indicator::factory()->create(['status' => 'active']);
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create(['indicator_id' => $indicator->id, 'user_id' => $user->id]);
        FinancialYear::factory()->create([
            'name' => 'Future FY',
            'start_date' => now()->addYear()->startOfYear(),
            'end_date' => now()->addYear()->endOfYear(),
        ]);
        FinancialYear::factory()->create([
            'name' => 'Available FY',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(11),
        ]);

        $this->actingAs($user)->get('/indicator-data-entries/create')
            ->assertOk()
            ->assertSee($indicator->name)
            ->assertDontSee('Available FY')
            ->assertDontSee('Future FY');
    }

    public function test_percentage_measurement_rejects_values_above_one_hundred(): void
    {
        $measurement = MeasurementType::factory()->create(['code' => 'percentage']);
        $indicator = Indicator::factory()->create(['measurement_type_id' => $measurement->id]);
        $financialYear = FinancialYear::factory()->started()->create();
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create(['indicator_id' => $indicator->id, 'user_id' => $user->id]);

        $this->actingAs($user)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 101,
        ])->assertJsonValidationErrors('actual_value');
    }
}
