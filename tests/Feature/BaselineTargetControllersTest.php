<?php

namespace Tests\Feature;

use App\Models\DimensionOption;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\ReportingPeriod;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaselineTargetControllersTest extends TestCase
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

    // --- Baseline ---

    public function test_authorized_user_can_set_a_baseline(): void
    {
        $user = $this->userWithRole('Thematic Manager');
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->create();

        $response = $this->actingAs($user)->postJson('/indicator-baselines', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'baseline_value' => 200000,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('indicator_baselines', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
        ]);
    }

    public function test_unauthorized_user_cannot_set_a_baseline(): void
    {
        $user = $this->userWithRole('Data Entry User');
        $indicator = Indicator::factory()->create();

        $response = $this->actingAs($user)->postJson('/indicator-baselines', [
            'indicator_id' => $indicator->id,
            'baseline_value' => 200000,
        ]);

        $response->assertForbidden();
    }

    public function test_duplicate_baseline_for_same_indicator_and_year_is_rejected(): void
    {
        $user = $this->userWithRole('Thematic Manager');
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->create();

        $this->actingAs($user)->postJson('/indicator-baselines', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'baseline_value' => 200000,
        ])->assertCreated();

        $response = $this->actingAs($user)->postJson('/indicator-baselines', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'baseline_value' => 250000,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('financial_year_id');
    }

    public function test_duplicate_baseline_with_no_year_set_is_rejected(): void
    {
        // Regression test for the phase-0 bug: a plain unique(indicator_id, financial_year_id)
        // would not catch this, since SQL never considers two NULLs equal.
        $user = $this->userWithRole('Thematic Manager');
        $indicator = Indicator::factory()->create();

        $this->actingAs($user)->postJson('/indicator-baselines', [
            'indicator_id' => $indicator->id,
            'baseline_value' => 200000,
        ])->assertCreated();

        $response = $this->actingAs($user)->postJson('/indicator-baselines', [
            'indicator_id' => $indicator->id,
            'baseline_value' => 250000,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('financial_year_id');
    }

    public function test_same_baseline_year_is_allowed_for_a_different_indicator(): void
    {
        $user = $this->userWithRole('Thematic Manager');
        $indicatorA = Indicator::factory()->create();
        $indicatorB = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->create();

        $this->actingAs($user)->postJson('/indicator-baselines', [
            'indicator_id' => $indicatorA->id,
            'financial_year_id' => $financialYear->id,
            'baseline_value' => 200000,
        ])->assertCreated();

        $response = $this->actingAs($user)->postJson('/indicator-baselines', [
            'indicator_id' => $indicatorB->id,
            'financial_year_id' => $financialYear->id,
            'baseline_value' => 300000,
        ]);

        $response->assertCreated();
    }

    // --- Target ---

    public function test_authorized_user_can_set_a_target(): void
    {
        $user = $this->userWithRole('Thematic Manager');
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->create();

        $response = $this->actingAs($user)->postJson('/indicator-targets', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 500000,
        ]);

        $response->assertCreated();
    }

    public function test_unauthorized_user_cannot_set_a_target(): void
    {
        $user = $this->userWithRole('Data Entry User');
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->create();

        $response = $this->actingAs($user)->postJson('/indicator-targets', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 500000,
        ]);

        $response->assertForbidden();
    }

    public function test_duplicate_annual_target_with_no_period_is_rejected(): void
    {
        // Regression test for the phase-0 bug: aggregate/annual targets (no reporting_period_id)
        // were not actually protected by the old unique constraint.
        $user = $this->userWithRole('Thematic Manager');
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->create();

        $this->actingAs($user)->postJson('/indicator-targets', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 500000,
        ])->assertCreated();

        $response = $this->actingAs($user)->postJson('/indicator-targets', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 600000,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('target_value');
    }

    public function test_target_tied_to_a_reporting_period_from_a_different_financial_year_is_rejected(): void
    {
        $user = $this->userWithRole('Thematic Manager');
        $indicator = Indicator::factory()->create();
        $financialYearA = FinancialYear::factory()->create();
        $financialYearB = FinancialYear::factory()->create();
        $periodInB = ReportingPeriod::factory()->create(['financial_year_id' => $financialYearB->id]);

        $response = $this->actingAs($user)->postJson('/indicator-targets', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYearA->id,
            'reporting_period_id' => $periodInB->id,
            'target_value' => 500000,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('reporting_period_id');
    }

    public function test_per_dimension_targets_do_not_collide_with_each_other(): void
    {
        $user = $this->userWithRole('Thematic Manager');
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->create();
        $optionA = DimensionOption::factory()->create();
        $optionB = DimensionOption::factory()->create();

        $this->actingAs($user)->postJson('/indicator-targets', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'dimension_option_id' => $optionA->id,
            'target_value' => 100000,
        ])->assertCreated();

        $response = $this->actingAs($user)->postJson('/indicator-targets', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'dimension_option_id' => $optionB->id,
            'target_value' => 150000,
        ]);

        $response->assertCreated();
    }

    public function test_duplicate_target_for_same_dimension_is_rejected(): void
    {
        $user = $this->userWithRole('Thematic Manager');
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->create();
        $option = DimensionOption::factory()->create();

        $this->actingAs($user)->postJson('/indicator-targets', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'dimension_option_id' => $option->id,
            'target_value' => 100000,
        ])->assertCreated();

        $response = $this->actingAs($user)->postJson('/indicator-targets', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'dimension_option_id' => $option->id,
            'target_value' => 120000,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('target_value');
    }
}
