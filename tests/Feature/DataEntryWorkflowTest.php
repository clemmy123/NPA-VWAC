<?php

namespace Tests\Feature;

use App\Models\DimensionOption;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorDimension;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataEntryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function assignedDataEntryUser(Indicator $indicator): User
    {
        $indicator->update(['status' => 'active']);
        $user = $this->userWithRole('Data Entry User');
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
            'location_level' => null,
            'location_id' => null,
        ]);

        return $user;
    }

    public function test_full_draft_to_submit_to_approve_lifecycle(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);
        $reviewer = $this->userWithRole('Thematic Manager');

        $storeResponse = $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 500,
        ]);
        $storeResponse->assertCreated();
        $storeResponse->assertJsonPath('data.status', 'draft');
        $entryId = $storeResponse->json('data.id');

        $submitResponse = $this->actingAs($entrant)->postJson("/indicator-data-entries/{$entryId}/submit");
        $submitResponse->assertOk();
        $submitResponse->assertJsonPath('data.status', 'submitted');

        $approveResponse = $this->actingAs($reviewer)->postJson("/indicator-data-entries/{$entryId}/approve", [
            'comment' => 'Looks good.',
        ]);
        $approveResponse->assertOk();
        $approveResponse->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('indicator_data_reviews', [
            'indicator_data_entry_id' => $entryId,
            'action' => 'approved',
        ]);
    }

    public function test_draft_to_submit_to_reject_to_resubmit_lifecycle(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);
        $reviewer = $this->userWithRole('Thematic Manager');

        $entry = IndicatorDataEntry::factory()->submitted()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entered_by' => $entrant->id,
        ]);

        $returnResponse = $this->actingAs($reviewer)->postJson("/indicator-data-entries/{$entry->id}/return", [
            'comment' => 'Please fix the activity description.',
        ]);
        $returnResponse->assertOk();
        $returnResponse->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('indicator_data_reviews', [
            'indicator_data_entry_id' => $entry->id,
            'action' => 'rejected',
        ]);

        $resubmitResponse = $this->actingAs($entrant)->postJson("/indicator-data-entries/{$entry->id}/submit");
        $resubmitResponse->assertOk();
        $resubmitResponse->assertJsonPath('data.status', 'submitted');
    }

    public function test_reconciliation_is_enforced_when_indicator_requires_it(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        IndicatorDimension::factory()->mustReconcile()->create(['indicator_id' => $indicator->id]);
        $entrant = $this->assignedDataEntryUser($indicator);

        $response = $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 100,
            'rows' => [
                ['label' => 'Male', 'value' => 30],
                ['label' => 'Female', 'value' => 40],
            ],
        ]);
        $response->assertCreated();
        $entryId = $response->json('data.id');

        // 30 + 40 = 70, does not match actual_value of 100.
        $mismatchResponse = $this->actingAs($entrant)->postJson("/indicator-data-entries/{$entryId}/submit");
        $mismatchResponse->assertUnprocessable()->assertJsonValidationErrors('actual_value');

        $this->actingAs($entrant)->putJson("/indicator-data-entries/{$entryId}", [
            'rows' => [
                ['label' => 'Male', 'value' => 60],
                ['label' => 'Female', 'value' => 40],
            ],
        ])->assertOk();

        $matchResponse = $this->actingAs($entrant)->postJson("/indicator-data-entries/{$entryId}/submit");
        $matchResponse->assertOk();
        $matchResponse->assertJsonPath('data.status', 'submitted');
    }

    public function test_reconciliation_is_not_enforced_when_indicator_does_not_require_it(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $response = $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 100,
            'rows' => [
                ['label' => 'Male', 'value' => 10],
            ],
        ]);
        $entryId = $response->json('data.id');

        $submitResponse = $this->actingAs($entrant)->postJson("/indicator-data-entries/{$entryId}/submit");
        $submitResponse->assertOk();
    }

    public function test_row_dimension_tags_are_persisted(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $option = DimensionOption::factory()->create();
        $entrant = $this->assignedDataEntryUser($indicator);

        $response = $this->actingAs($entrant)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 0,
            'rows' => [
                ['label' => 'Youth', 'value' => 25, 'dimension_option_ids' => [$option->id]],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.rows.0.dimension_option_ids', [$option->id]);
    }

    public function test_user_without_an_assignment_cannot_create_an_entry_for_the_indicator(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $user = $this->userWithRole('Data Entry User');

        $response = $this->actingAs($user)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 0,
        ]);

        $response->assertForbidden();
    }

    public function test_legacy_assignment_does_not_restrict_collection_location(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $regionA = Region::factory()->create();
        $regionB = Region::factory()->create();

        $user = $this->userWithRole('Data Entry User');
        $indicator->update(['status' => 'active']);
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
            'location_level' => 'region',
            'location_id' => $regionA->region_id,
        ]);

        $wrongRegionResponse = $this->actingAs($user)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'location_level' => 'region',
            'location_id' => $regionB->region_id,
            'actual_value' => 0,
        ]);
        $wrongRegionResponse->assertCreated();

        $rightRegionResponse = $this->actingAs($user)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'location_level' => 'region',
            'location_id' => $regionA->region_id,
            'actual_value' => 0,
        ]);
        $rightRegionResponse->assertCreated();
    }

    public function test_super_admin_bypasses_assignment_scoping(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();
        $admin = $this->userWithRole('Super Admin');

        $response = $this->actingAs($admin)->postJson('/indicator-data-entries', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 0,
        ]);

        $response->assertCreated();
    }

    public function test_submitted_entry_cannot_be_edited(): void
    {
        $indicator = Indicator::factory()->create();
        $entrant = $this->assignedDataEntryUser($indicator);
        $entry = IndicatorDataEntry::factory()->submitted()->create([
            'indicator_id' => $indicator->id,
            'entered_by' => $entrant->id,
        ]);

        $response = $this->actingAs($entrant)->putJson("/indicator-data-entries/{$entry->id}", [
            'actual_value' => 999,
        ]);

        $response->assertForbidden();
    }
}
