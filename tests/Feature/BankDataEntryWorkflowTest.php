<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * End-to-end: a Super Admin sets up a reporting organization (a bank) and one
 * of its staff as a locally-authenticated Data Entry User, assigns that user
 * to a single indicator, and the bank user logs in through the separate
 * local-login flow and can only report on the indicator they were assigned.
 */
class BankDataEntryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_a_bank_user_can_be_provisioned_and_report_only_on_their_assigned_indicator(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        // 1. Super Admin creates the organization (the bank).
        $orgType = OrganizationType::factory()->create(['code' => 'bank', 'name' => 'Bank']);
        $this->actingAs($admin)->post(route('organizations.store'), [
            'name' => 'NMB Bank', 'organization_type_id' => $orgType->id,
        ])->assertRedirect(route('organizations.index'));
        $bank = Organization::where('name', 'NMB Bank')->firstOrFail();

        // 2. Super Admin creates a local-login user for the bank's staff.
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'NMB Reporter',
            'email' => 'reporter@nmb.example.test',
            'organization_id' => $bank->id,
            'auth_provider' => 'local',
            'password' => 'bank-secret-1',
            'password_confirmation' => 'bank-secret-1',
            'status' => 'active',
            'role' => 'Data Entry User',
        ])->assertRedirect(route('users.index'));
        $bankUser = User::where('email', 'reporter@nmb.example.test')->firstOrFail();

        // 3. Super Admin assigns the bank user to exactly one indicator.
        $assignedIndicator = Indicator::factory()->create(['name' => 'Loans disbursed to women']);
        $unassignedIndicator = Indicator::factory()->create(['name' => 'Unrelated indicator']);
        $financialYear = FinancialYear::factory()->started()->create();

        $this->actingAs($admin)->post(route('indicator-data-assignments.store'), [
            'indicator_id' => $assignedIndicator->id,
            'user_id' => $bankUser->id,
            'organization_id' => $bank->id,
        ])->assertRedirect(route('indicator-data-assignments.index'));

        // 4. The bank user signs in through the separate local-login page, not Jumuishi.
        // actingAs() persists for the rest of the test unless the guard is explicitly
        // cleared first — otherwise the later assertAuthenticatedAs() below would still
        // see $admin rather than the user local-login just authenticated.
        $this->post(route('logout'));
        $this->post(route('local-login.store'), [
            'email' => 'reporter@nmb.example.test',
            'password' => 'bank-secret-1',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($bankUser);

        // 5. They can create a data entry for the indicator they were assigned...
        $this->actingAs($bankUser)->post(route('indicator-data-entries.store'), [
            'indicator_id' => $assignedIndicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 42,
            'currency' => 'TZS',
            'organization_id' => $bank->id,
        ])->assertRedirect(route('indicator-data-entries.index'));

        $this->assertDatabaseHas('indicator_data_entries', [
            'indicator_id' => $assignedIndicator->id,
            'entered_by' => $bankUser->id,
            'status' => 'draft',
        ]);

        // 6. ...but not for an indicator they were never assigned.
        $this->actingAs($bankUser)->post(route('indicator-data-entries.store'), [
            'indicator_id' => $unassignedIndicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 7,
            'currency' => 'TZS',
        ])->assertForbidden();
    }

    public function test_deactivating_a_bank_user_prevents_local_login(): void
    {
        $bankUser = User::factory()->create([
            'auth_provider' => 'local',
            'password_login_enabled' => true,
            'status' => 'inactive',
            'password' => Hash::make('secret-pass'),
        ]);

        $this->post(route('local-login.store'), [
            'email' => $bankUser->email,
            'password' => 'secret-pass',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
