<?php

namespace Tests\Unit;

use App\Models\Council;
use App\Models\District;
use App\Models\Division;
use App\Models\Indicator;
use App\Models\IndicatorApprovalAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorDimension;
use App\Models\Region;
use App\Models\User;
use App\Models\Ward;
use App\Services\IndicatorReviewService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IndicatorReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    private IndicatorReviewService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->service = new IndicatorReviewService;
    }

    public function test_submit_rejects_a_user_submitting_someone_elses_entry(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $entry = IndicatorDataEntry::factory()->create(['entered_by' => $owner->id, 'status' => 'draft']);

        $this->expectException(AuthorizationException::class);

        $this->service->submit($entry, $otherUser);
    }

    public function test_submit_enforces_reconciliation_when_the_indicator_requires_it(): void
    {
        $indicator = Indicator::factory()->create();
        IndicatorDimension::factory()->mustReconcile()->create(['indicator_id' => $indicator->id]);
        $user = User::factory()->create();
        $entry = IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'entered_by' => $user->id,
            'status' => 'draft',
            'actual_value' => 100,
        ]);
        $entry->rows()->create(['label' => 'Male', 'value' => 30]);

        $this->expectException(ValidationException::class);

        $this->service->submit($entry, $user);
    }

    public function test_submit_moves_a_draft_entry_to_submitted(): void
    {
        $user = User::factory()->create();
        $entry = IndicatorDataEntry::factory()->create(['entered_by' => $user->id, 'status' => 'draft']);

        $submitted = $this->service->submit($entry, $user);

        $this->assertSame('submitted', $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
    }

    public function test_submit_rejects_a_numeric_entry_without_an_actual_value(): void
    {
        $user = User::factory()->create();
        $entry = IndicatorDataEntry::factory()->create([
            'entered_by' => $user->id,
            'status' => 'draft',
            'actual_value' => null,
        ]);

        try {
            $this->service->submit($entry, $user);
            $this->fail('Expected submit to reject a missing actual value.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('actual_value', $exception->errors());
        }
    }

    public function test_approve_rejects_a_non_submitted_entry(): void
    {
        $reviewer = User::factory()->create();
        $entry = IndicatorDataEntry::factory()->create(['status' => 'draft']);

        $this->expectException(ValidationException::class);

        $this->service->approve($entry, $reviewer, null);
    }

    public function test_approve_records_a_review_and_marks_the_entry_approved(): void
    {
        $reviewer = User::factory()->create();
        $entry = IndicatorDataEntry::factory()->submitted()->create();

        $approved = $this->service->approve($entry, $reviewer, 'Looks good.');

        $this->assertSame('approved', $approved->status);
        $this->assertSame($reviewer->id, $approved->approved_by);
        $this->assertDatabaseHas('indicator_data_reviews', [
            'indicator_data_entry_id' => $entry->id,
            'action' => 'approved',
            'comment' => 'Looks good.',
        ]);
    }

    public function test_return_to_submitter_marks_the_entry_rejected(): void
    {
        $reviewer = User::factory()->create();
        $entry = IndicatorDataEntry::factory()->submitted()->create();

        $rejected = $this->service->returnToSubmitter($entry, $reviewer, 'Please fix.');

        $this->assertSame('rejected', $rejected->status);
        $this->assertDatabaseHas('indicator_data_reviews', [
            'indicator_data_entry_id' => $entry->id,
            'action' => 'rejected',
            'comment' => 'Please fix.',
        ]);
    }

    public function test_ward_entry_follows_ward_council_district_region_approval_chain(): void
    {
        $region = Region::factory()->create();
        $district = District::factory()->create(['region_id' => $region->region_id]);
        $council = Council::factory()->create(['district_id' => $district->district_id]);
        $division = Division::factory()->create(['council_id' => $council->council_id]);
        $ward = Ward::factory()->create(['division_id' => $division->division_id]);
        $indicator = Indicator::factory()->create(['requires_hierarchical_approval' => true]);
        $owner = User::factory()->create();
        $entry = IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'entered_by' => $owner->id,
            'location_level' => 'ward',
            'location_id' => $ward->ward_id,
            'status' => 'draft',
        ]);

        $submitted = $this->service->submit($entry, $owner);
        $this->assertSame('pending_approval', $submitted->status);
        $this->assertSame(['ward', 'council', 'district', 'region'], $submitted->approvalSteps()->pluck('location_level')->all());

        foreach ([['ward', $ward->ward_id], ['council', $council->council_id], ['district', $district->district_id], ['region', $region->region_id]] as $index => [$level, $id]) {
            $approver = User::factory()->create();
            $approver->assignRole('Data Approver');
            IndicatorApprovalAssignment::create(['user_id' => $approver->id, 'location_level' => $level, 'location_id' => $id, 'is_active' => true]);
            $submitted = $this->service->approve($submitted->fresh(), $approver, 'Approved at '.$level);
            $this->assertSame($index === 3 ? 'approved' : 'pending_approval', $submitted->status);
        }
    }
}
