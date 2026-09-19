<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\MeasurementType;
use App\Models\Region;
use App\Models\ReportingPeriod;
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
        $indicator->update(['status' => 'active']);
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

    public function test_budget_can_be_left_empty_when_the_indicator_has_budget_implication(): void
    {
        $indicator = Indicator::factory()->create(['has_budget_implication' => true]);
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $response = $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 0,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('indicator_data_entries', [
            'indicator_id' => $indicator->id,
            'budget_allocated' => null,
        ]);
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
            'actual_value' => 0,
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
            'actual_value' => 0,
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

    public function test_text_measurement_stores_text_instead_of_a_numeric_actual(): void
    {
        $measurement = MeasurementType::factory()->create(['code' => 'text', 'name' => 'Text']);
        $indicator = Indicator::factory()->create(['measurement_type_id' => $measurement->id]);
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_text' => 'Implementation is progressing according to plan.',
        ])->assertCreated();

        $this->assertDatabaseHas('indicator_data_entries', [
            'indicator_id' => $indicator->id,
            'actual_text' => 'Implementation is progressing according to plan.',
            'actual_value' => null,
        ]);
    }

    public function test_monthly_indicator_requires_a_configured_month_period(): void
    {
        $indicator = Indicator::factory()->create(['reporting_frequency' => 'monthly']);
        $financialYear = FinancialYear::factory()->started()->create();
        ReportingPeriod::factory()->create([
            'financial_year_id' => $financialYear->id,
            'code' => 'M01',
            'period_type' => 'month',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);
        $entrant = $this->assignedDataEntryUser($indicator);

        $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('reporting_period_id');
    }

    public function test_start_collection_creates_today_draft_and_then_opens_indicator_fields(): void
    {
        $indicator = Indicator::factory()->create(['status' => 'active']);
        $financialYear = FinancialYear::factory()->create([
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(11),
            'is_active' => true,
        ]);
        $entrant = $this->assignedDataEntryUser($indicator);

        $response = $this->actingAs($entrant)->post(route('indicator-data-entries.start'), [
            'project_id' => $indicator->thematicArea->project_id,
            'thematic_area_id' => $indicator->thematic_area_id,
            'indicator_id' => $indicator->id,
        ]);

        $entry = IndicatorDataEntry::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('indicator-data-entries.edit', $entry));
        $this->assertSame($financialYear->id, $entry->financial_year_id);
        $this->assertSame(now()->toDateString(), $entry->entry_date->toDateString());
    }

    public function test_start_collection_rejects_an_indicator_from_another_thematic_area(): void
    {
        $indicator = Indicator::factory()->create(['status' => 'active']);
        $otherIndicator = Indicator::factory()->create(['status' => 'active']);
        $entrant = $this->assignedDataEntryUser($indicator);

        $this->actingAs($entrant)->post(route('indicator-data-entries.start'), [
            'project_id' => $otherIndicator->thematicArea->project_id,
            'thematic_area_id' => $otherIndicator->thematic_area_id,
            'indicator_id' => $indicator->id,
        ])->assertSessionHasErrors('indicator_id');

        $this->assertDatabaseCount('indicator_data_entries', 0);
    }

    public function test_collection_can_save_multiple_activity_participant_breakdowns(): void
    {
        $indicator = Indicator::factory()->create(['requires_activity' => true]);
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'actual_value' => 0,
            'activities' => [
                ['name' => 'Community dialogue', 'participants_total' => 30, 'women' => 12, 'men' => 8, 'children' => 9, 'other' => 1],
                ['name' => 'School session', 'participants_total' => 20, 'women' => 4, 'men' => 3, 'children' => 13, 'other' => 0],
            ],
        ])->assertCreated();

        $this->assertDatabaseCount('indicator_data_entry_activities', 2);
        $this->assertDatabaseHas('indicator_data_entry_activities', ['name' => 'Community dialogue', 'women' => 12, 'children' => 9]);
    }

    public function test_numeric_actual_value_cannot_be_left_empty(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('actual_value');

        $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors('actual_value');
    }

    public function test_numeric_actual_value_accepts_zero_and_rejects_negatives(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 0,
        ])->assertCreated();

        $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => -1,
        ])->assertUnprocessable()->assertJsonValidationErrors('actual_value');
    }

    public function test_updating_a_collection_cannot_clear_the_actual_value(): void
    {
        $indicator = Indicator::factory()->create();
        $entrant = $this->assignedDataEntryUser($indicator);
        $entry = IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'entered_by' => $entrant->id,
            'actual_value' => 12,
        ]);

        $this->actingAs($entrant)->putJson("/indicator-data-entries/{$entry->id}", [
            'actual_value' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors('actual_value');
    }
}
