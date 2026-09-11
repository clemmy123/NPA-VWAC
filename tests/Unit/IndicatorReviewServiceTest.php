<?php

namespace Tests\Unit;

use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorDimension;
use App\Models\User;
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
}
