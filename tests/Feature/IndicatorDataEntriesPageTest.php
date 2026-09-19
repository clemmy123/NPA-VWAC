<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorDataEntriesPageTest extends TestCase
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

    public function test_authorized_user_sees_the_data_entries_table_page(): void
    {
        $user = $this->userWithRole('Super Admin');
        $entry = IndicatorDataEntry::factory()->submitted()->create(['entered_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('indicator-data-entries.index'));

        $response->assertOk();
        $response->assertSee('Data Collections');
        $response->assertSee('table-card', false);
        $response->assertSee($entry->indicator->name);
        $response->assertSee('id="collectionEditorModal"', false);
        $response->assertSee('id="collection-records"', false);
        $response->assertSee('row-view-btn', false);
        $response->assertDontSee('indicatorSummary', false);
        $response->assertDontSee(route('indicator-data-entries.create'), false);
    }

    public function test_manager_sees_submitted_entries_but_not_another_users_drafts(): void
    {
        $manager = $this->userWithRole('Thematic Manager');
        $collector = $this->userWithRole('Data Entry User');
        $draftIndicator = Indicator::factory()->create(['name' => 'Private draft indicator']);
        $submittedIndicator = Indicator::factory()->create(['name' => 'Submitted review indicator']);
        $financialYear = FinancialYear::factory()->create();
        IndicatorDataEntry::factory()->create([
            'indicator_id' => $draftIndicator->id,
            'financial_year_id' => $financialYear->id,
            'entered_by' => $collector->id,
            'status' => 'draft',
        ]);
        $submitted = IndicatorDataEntry::factory()->submitted()->create([
            'indicator_id' => $submittedIndicator->id,
            'financial_year_id' => $financialYear->id,
            'entered_by' => $collector->id,
        ]);

        $response = $this->actingAs($manager)->get(route('indicator-data-entries.index'));

        $response->assertOk();
        $response->assertDontSee('Private draft indicator');
        $response->assertSee('Submitted review indicator');
        $response->assertSee('row-view-btn', false);
        $response->assertSee(route('indicator-data-entries.show', $submitted), false);
        $response->assertDontSee('mdi-dots-vertical', false);
        $response->assertDontSee('mdi-eye-outline', false);
    }

    public function test_thematic_manager_sees_workspace_cards_submitted_then_approved_then_not_collected(): void
    {
        $manager = $this->userWithRole('Thematic Manager');
        $collector = $this->userWithRole('Data Entry User');
        $financialYear = FinancialYear::factory()->create();
        $thematicArea = ThematicArea::factory()->create();
        $notCollected = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'AAA not collected indicator',
            'status' => 'active',
        ]);
        $approved = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'ZZZ approved indicator',
            'status' => 'active',
        ]);
        $submitted = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'MMM submitted indicator',
            'status' => 'active',
        ]);
        IndicatorDataEntry::factory()->submitted()->create([
            'indicator_id' => $submitted->id,
            'financial_year_id' => $financialYear->id,
            'entered_by' => $collector->id,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $approved->id,
            'financial_year_id' => $financialYear->id,
            'entered_by' => $collector->id,
        ]);

        $response = $this->actingAs($manager)->get(route('indicator-data-entries.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            $submitted->name,
            $approved->name,
            $notCollected->name,
        ]);
        $response->assertSee('#collection-records', false);
        $response->assertSee('s-badge s-investigation', false);
        $response->assertSee('s-badge s-active', false);
    }

    public function test_data_entry_user_sees_workspace_cards_draft_then_not_collected_then_approved(): void
    {
        $collector = $this->userWithRole('Data Entry User');
        $financialYear = FinancialYear::factory()->create();
        $thematicArea = ThematicArea::factory()->create();
        $approved = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'AAA approved collector indicator',
            'status' => 'active',
        ]);
        $notCollected = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'MMM not collected collector indicator',
            'status' => 'active',
        ]);
        $draft = Indicator::factory()->create([
            'thematic_area_id' => $thematicArea->id,
            'name' => 'ZZZ draft collector indicator',
            'status' => 'active',
        ]);

        foreach ([$approved, $notCollected, $draft] as $indicator) {
            IndicatorDataAssignment::factory()->create([
                'indicator_id' => $indicator->id,
                'user_id' => $collector->id,
            ]);
        }

        IndicatorDataEntry::factory()->create([
            'indicator_id' => $draft->id,
            'financial_year_id' => $financialYear->id,
            'entered_by' => $collector->id,
            'status' => 'draft',
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $approved->id,
            'financial_year_id' => $financialYear->id,
            'entered_by' => $collector->id,
        ]);

        $response = $this->actingAs($collector)->get(route('indicator-data-entries.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            $draft->name,
            $notCollected->name,
            $approved->name,
        ]);
    }

    public function test_draft_collections_are_not_counted_or_listed_until_submitted(): void
    {
        $collector = $this->userWithRole('Data Entry User');
        $financialYear = FinancialYear::factory()->create();
        $indicator = Indicator::factory()->create([
            'name' => 'Countable submitted collections',
            'status' => 'active',
        ]);
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $collector->id,
        ]);
        IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entered_by' => $collector->id,
            'status' => 'draft',
            'actual_value' => 86421,
        ]);
        $submitted = IndicatorDataEntry::factory()->submitted()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entered_by' => $collector->id,
            'actual_value' => 86422,
        ]);
        IndicatorDataEntry::factory()->approved()->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entered_by' => $collector->id,
            'actual_value' => 86423,
        ]);

        $response = $this->actingAs($collector)->get(route('indicator-data-entries.index'));

        $response->assertOk();
        $response->assertSee('<strong>2</strong> collections', false);
        $response->assertDontSee('<strong>3</strong> collections', false);
        $response->assertSee($indicator->name);
        $response->assertSee(route('indicator-data-entries.show', $submitted), false);
        $response->assertDontSee('86,421');
        $response->assertDontSee('86,423');

        $show = $this->actingAs($collector)->get(route('indicator-data-entries.show', $submitted));
        $show->assertOk();
        $show->assertSee('<span class="collection-count-value">2</span>', false);
        $show->assertSee('collected');
        $show->assertSee('Collection 1');
        $show->assertSee('Collection 2');
        $show->assertDontSee('Reported 2 times');
        $show->assertDontSee('Report 1');
        $show->assertSee('86,422');
        $show->assertSee('86,423');
        $show->assertDontSee('86,421');
    }

    public function test_view_history_opens_the_collection_records_table_without_a_popup(): void
    {
        $user = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create([
            'name' => 'History jump indicator',
            'status' => 'active',
        ]);
        IndicatorDataEntry::factory()->submitted()->create([
            'indicator_id' => $indicator->id,
            'entered_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('indicator-data-entries.index', [
            'indicator_id' => $indicator->id,
        ]));

        $response->assertOk();
        $response->assertSee('Collection records');
        $response->assertSee('History jump indicator');
        $response->assertSee('Show all');
        $response->assertSee('show-all-btn', false);
        $response->assertSee('filter-card report-filters mb-3 d-none', false);
        $response->assertSee('row g-3 mb-4 d-none', false);
        $response->assertDontSee('indicatorSummary', false);
    }

    public function test_reviewer_sees_collection_summary_with_approve_and_rollback_actions(): void
    {
        $manager = $this->userWithRole('Thematic Manager');
        $collector = $this->userWithRole('Data Entry User');
        $entry = IndicatorDataEntry::factory()->submitted()->create([
            'entered_by' => $collector->id,
            'actual_value' => 42,
            'remarks' => 'Summary review remarks',
        ]);

        $response = $this->actingAs($manager)->get(route('indicator-data-entries.show', $entry));

        $response->assertOk();
        $response->assertSee('Collection summary');
        $response->assertSee('collection-summary-thematic', false);
        $response->assertSeeInOrder([
            $entry->indicator->thematicArea->name,
            $entry->indicator->name,
        ]);
        $response->assertSee('Summary review remarks');
        $response->assertSee('Approve');
        $response->assertSee('Rollback');
        $response->assertSee('data-confirm="Approve this collection?"', false);
        $response->assertSee('data-confirm="Rollback this collection to the submitter?"', false);
        $response->assertSee('<span class="collection-count-value">1</span>', false);
        $response->assertSee('collected');
        $response->assertSee('Collection 1');
        $response->assertDontSee('Reported 1 time');
        $response->assertDontSee('Report 1');
        $response->assertDontSee('mdi-dots-vertical', false);
    }

    public function test_manager_cannot_open_another_users_draft_summary(): void
    {
        $manager = $this->userWithRole('Thematic Manager');
        $collector = $this->userWithRole('Data Entry User');
        $entry = IndicatorDataEntry::factory()->create([
            'entered_by' => $collector->id,
            'status' => 'draft',
        ]);

        $this->actingAs($manager)
            ->get(route('indicator-data-entries.show', $entry))
            ->assertForbidden();
    }

    public function test_embedded_submit_form_uses_app_confirm_instead_of_browser_confirm(): void
    {
        $user = $this->userWithRole('Super Admin');
        $entry = IndicatorDataEntry::factory()->create(['entered_by' => $user->id, 'status' => 'draft']);

        $response = $this->actingAs($user)->get(route('indicator-data-entries.edit', [
            'indicator_data_entry' => $entry,
            'embedded' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('data-confirm="Submit this completed collection for review?"', false);
        $response->assertDontSee('return confirm(', false);
    }

    public function test_data_collector_embedded_submit_notifies_parent_with_success_message(): void
    {
        $collector = $this->userWithRole('Data Entry User');
        $entry = IndicatorDataEntry::factory()->create(['entered_by' => $collector->id, 'status' => 'draft']);

        $response = $this->actingAs($collector)->post(route('indicator-data-entries.submit', [
            'indicator_data_entry' => $entry,
            'embedded' => 1,
        ]));

        $response->assertRedirect(route('indicator-data-entries.edit', [
            'indicator_data_entry' => $entry,
            'embedded' => 1,
            'submitted' => 1,
        ]));
        $this->assertDatabaseHas('indicator_data_entries', ['id' => $entry->id, 'status' => 'submitted']);

        $page = $this->actingAs($collector)->get(route('indicator-data-entries.edit', [
            'indicator_data_entry' => $entry,
            'embedded' => 1,
            'submitted' => 1,
        ]));

        $page->assertOk();
        $page->assertSee('collection-submitted', false);
        $page->assertSee('Data collection submitted for review.', false);
    }

    public function test_data_collections_page_shows_submit_success_on_the_parent(): void
    {
        $collector = $this->userWithRole('Data Entry User');

        $response = $this->actingAs($collector)->get(route('indicator-data-entries.index'));

        $response->assertOk();
        $response->assertSee('_pendingCollectionSuccess', false);
        $response->assertSee('window.showAppMessage(\'success\', "Success", successMessage)', false);
    }

    public function test_unauthorized_user_is_forbidden_from_the_data_entries_create_page(): void
    {
        $user = $this->userWithRole('Project Manager');

        $response = $this->actingAs($user)->get(route('indicator-data-entries.create'));

        $response->assertForbidden();
    }

    public function test_super_admin_can_create_a_data_entry_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->started()->create();

        $response = $this->actingAs($user)->post(route('indicator-data-entries.store'), [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 120,
            'currency' => 'TZS',
            'rows' => [
                ['label' => 'Male', 'value' => 70],
                ['label' => 'Female', 'value' => 50],
            ],
        ]);

        $response->assertRedirect(route('indicator-data-entries.index'));
        $this->assertDatabaseHas('indicator_data_entries', [
            'indicator_id' => $indicator->id,
            'status' => 'draft',
            'entered_by' => $user->id,
        ]);
        $this->assertDatabaseHas('indicator_data_entry_rows', ['label' => 'Male', 'value' => 70]);
    }

    public function test_saving_a_draft_accepts_a_typed_numeric_actual_value(): void
    {
        $user = $this->userWithRole('Super Admin');
        $entry = IndicatorDataEntry::factory()->create([
            'entered_by' => $user->id,
            'status' => 'draft',
            'actual_value' => null,
        ]);

        $this->actingAs($user)->put(route('indicator-data-entries.update', $entry), [
            'indicator_id' => $entry->indicator_id,
            'financial_year_id' => $entry->financial_year_id,
            'entry_date' => now()->toDateString(),
            'actual_value' => '20',
            'currency' => 'TZS',
        ])->assertRedirect(route('indicator-data-entries.index'));

        $this->assertDatabaseHas('indicator_data_entries', [
            'id' => $entry->id,
            'actual_value' => 20,
        ]);
    }

    public function test_embedded_submit_saves_the_actual_value_before_review(): void
    {
        $collector = $this->userWithRole('Data Entry User');
        $indicator = Indicator::factory()->create(['status' => 'active']);
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $collector->id,
        ]);
        $entry = IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'entered_by' => $collector->id,
            'status' => 'draft',
            'actual_value' => null,
        ]);

        $this->actingAs($collector)->put(route('indicator-data-entries.update', [
            'indicator_data_entry' => $entry,
            'embedded' => 1,
        ]), [
            'indicator_id' => $entry->indicator_id,
            'financial_year_id' => $entry->financial_year_id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 20,
            'intent' => 'submit',
            'currency' => 'TZS',
        ])->assertRedirect(route('indicator-data-entries.edit', [
            'indicator_data_entry' => $entry,
            'embedded' => 1,
            'submitted' => 1,
        ]));

        $this->assertDatabaseHas('indicator_data_entries', [
            'id' => $entry->id,
            'actual_value' => 20,
            'status' => 'submitted',
        ]);
    }

    public function test_super_admin_can_update_a_draft_data_entry_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $entry = IndicatorDataEntry::factory()->create();

        $response = $this->actingAs($user)->put(route('indicator-data-entries.update', $entry), [
            'indicator_id' => $entry->indicator_id,
            'financial_year_id' => $entry->financial_year_id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 999,
            'currency' => 'TZS',
        ]);

        $response->assertRedirect(route('indicator-data-entries.index'));
        $this->assertDatabaseHas('indicator_data_entries', ['id' => $entry->id, 'actual_value' => 999]);
    }

    public function test_owner_can_submit_a_draft_data_entry(): void
    {
        $user = $this->userWithRole('Super Admin');
        $entry = IndicatorDataEntry::factory()->create(['entered_by' => $user->id]);

        $response = $this->actingAs($user)->post(route('indicator-data-entries.submit', $entry));

        $response->assertRedirect(route('indicator-data-entries.index'));
        $this->assertDatabaseHas('indicator_data_entries', ['id' => $entry->id, 'status' => 'submitted']);
    }

    public function test_reviewer_can_approve_a_submitted_data_entry(): void
    {
        $reviewer = $this->userWithRole('Thematic Manager');
        $entry = IndicatorDataEntry::factory()->submitted()->create();

        $response = $this->actingAs($reviewer)->post(route('indicator-data-entries.approve', $entry));

        $response->assertRedirect(route('indicator-data-entries.index'));
        $this->assertDatabaseHas('indicator_data_entries', ['id' => $entry->id, 'status' => 'approved']);
        $this->assertDatabaseHas('indicator_data_reviews', ['indicator_data_entry_id' => $entry->id, 'action' => 'approved']);
    }

    public function test_reviewer_can_return_a_submitted_data_entry(): void
    {
        $reviewer = $this->userWithRole('Thematic Manager');
        $entry = IndicatorDataEntry::factory()->submitted()->create();

        $response = $this->actingAs($reviewer)->post(route('indicator-data-entries.return', $entry));

        $response->assertRedirect(route('indicator-data-entries.index'));
        $this->assertDatabaseHas('indicator_data_entries', ['id' => $entry->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('indicator_data_reviews', ['indicator_data_entry_id' => $entry->id, 'action' => 'rejected']);
    }

    public function test_collection_form_requires_a_numeric_actual_value_from_zero(): void
    {
        $user = $this->userWithRole('Super Admin');
        $entry = IndicatorDataEntry::factory()->create(['entered_by' => $user->id, 'status' => 'draft']);

        $html = $this->actingAs($user)->get(route('indicator-data-entries.edit', $entry))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*id="actual_value"[^>]*>/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="actual_value"[^>]*\brequired\b|<input[^>]*\brequired\b[^>]*name="actual_value"/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="actual_value"[^>]*min="0"|<input[^>]*min="0"[^>]*name="actual_value"/', $html);
        $this->assertStringContainsString('js-actual-required-mark', $html);
    }
}
