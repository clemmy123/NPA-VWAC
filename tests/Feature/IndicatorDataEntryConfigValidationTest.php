<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorDataEntryConfigValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function assignedDataEntryUser(Indicator $indicator): User
    {
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
            'location_level' => null,
            'location_id' => null,
        ]);

        return $user;
    }

    public function test_creating_an_entry_requires_location_when_the_indicator_requires_it(): void
    {
        $indicator = Indicator::factory()->create(['requires_location' => true]);
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $response = $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('location_level');
    }

    public function test_creating_an_entry_requires_activity_name_when_the_indicator_requires_it(): void
    {
        $indicator = Indicator::factory()->create(['requires_activity' => true]);
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $response = $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('activity_name');
    }

    public function test_creating_an_entry_requires_budget_allocated_when_the_indicator_has_budget_implication(): void
    {
        $indicator = Indicator::factory()->create(['has_budget_implication' => true]);
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $response = $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('budget_allocated');
    }

    public function test_creating_an_entry_succeeds_when_all_required_config_fields_are_present(): void
    {
        $indicator = Indicator::factory()->create([
            'requires_location' => true,
            'requires_activity' => true,
            'has_budget_implication' => true,
        ]);
        $financialYear = FinancialYear::factory()->started()->create();
        $region = Region::factory()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $response = $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'activity_name' => 'Community dialogue session',
            'location_level' => 'region',
            'location_id' => $region->region_id,
            'budget_allocated' => 5000,
        ]);

        $response->assertCreated();
    }

    public function test_config_fields_are_not_required_when_the_indicator_does_not_require_them(): void
    {
        $indicator = Indicator::factory()->create([
            'requires_location' => false,
            'requires_activity' => false,
            'has_budget_implication' => false,
        ]);
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $response = $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
        ]);

        $response->assertCreated();
    }

    public function test_updating_an_entry_still_enforces_the_indicators_config_using_existing_values(): void
    {
        $indicator = Indicator::factory()->create(['requires_activity' => true]);
        $entrant = $this->assignedDataEntryUser($indicator);
        $entry = IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'entered_by' => $entrant->id,
            'activity_name' => 'Existing activity',
        ]);

        $response = $this->actingAs($entrant)->putJson("/indicator-data-entries/{$entry->id}", [
            'activity_name' => '',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('activity_name');
    }

    public function test_updating_an_entry_without_touching_activity_name_keeps_passing_when_required(): void
    {
        $indicator = Indicator::factory()->create(['requires_activity' => true]);
        $entrant = $this->assignedDataEntryUser($indicator);
        $entry = IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'entered_by' => $entrant->id,
            'activity_name' => 'Existing activity',
        ]);

        $response = $this->actingAs($entrant)->putJson("/indicator-data-entries/{$entry->id}", [
            'remarks' => 'Just updating remarks.',
        ]);

        $response->assertOk();
    }
}
