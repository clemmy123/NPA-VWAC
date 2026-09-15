<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
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
        $entry = IndicatorDataEntry::factory()->create();

        $response = $this->actingAs($user)->get(route('indicator-data-entries.index'));

        $response->assertOk();
        $response->assertSee('Data Collections');
        $response->assertSee('table-card', false);
        $response->assertSee($entry->indicator->name);
        $response->assertSee(route('indicator-data-entries.create'), false);
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
}
