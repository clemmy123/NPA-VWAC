<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\Organization;
use App\Models\ReportingPeriod;
use App\Models\User;
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

    public function test_a_data_entry_user_only_sees_indicators_they_are_assigned_to(): void
    {
        $assignedIndicator = Indicator::factory()->create();
        $otherIndicator = Indicator::factory()->create();

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
        $indicator = Indicator::factory()->create();

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

    public function test_a_data_entry_user_cannot_view_an_indicator_they_are_not_assigned_to(): void
    {
        $indicator = Indicator::factory()->create();

        $user = User::factory()->create();
        $user->assignRole('Data Entry User');

        $this->actingAs($user)->getJson("/indicators/{$indicator->id}")->assertForbidden();
    }

    public function test_the_new_entry_forms_indicator_dropdown_is_scoped_to_the_data_entry_users_assignments(): void
    {
        $assignedIndicator = Indicator::factory()->create(['name' => 'Assigned Indicator']);
        $otherIndicator = Indicator::factory()->create(['name' => 'Someone Elses Indicator']);

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

    public function test_a_thematic_manager_sees_every_indicator_without_needing_an_assignment(): void
    {
        $indicator = Indicator::factory()->create();

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
        ]);

        $response->assertCreated();
    }

    public function test_an_organization_assignment_cannot_be_used_to_submit_for_another_organization(): void
    {
        $assignedOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $indicator = Indicator::factory()->create(['collection_scope' => 'institutional']);
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
        ])->assertForbidden();

        $this->actingAs($user)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'organization_id' => $assignedOrganization->id,
        ])->assertCreated();
    }

    public function test_indicator_reporting_level_is_enforced(): void
    {
        $indicator = Indicator::factory()->create([
            'requires_location' => true,
            'reporting_location_level' => 'council',
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
            'location_id' => \App\Models\Region::factory()->create()->region_id,
        ])->assertJsonValidationErrors('location_level');
    }

    public function test_a_council_scoped_user_can_submit_data_down_to_street_level(): void
    {
        $council = \App\Models\Council::factory()->create();
        $division = \App\Models\Division::factory()->create(['council_id' => $council->council_id]);
        $ward = \App\Models\Ward::factory()->create(['division_id' => $division->division_id]);
        $street = \App\Models\VillageMtaa::factory()->create(['ward_id' => $ward->ward_id, 'type' => 'mtaa']);
        $indicator = Indicator::factory()->create([
            'requires_location' => true,
            'reporting_location_level' => 'council',
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
}
