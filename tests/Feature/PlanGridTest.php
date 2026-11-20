<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorPlan;
use App\Models\Intervention;
use App\Models\MeasurementType;
use App\Models\Organization;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanGridTest extends TestCase
{
    use RefreshDatabase;

    private ThematicArea $area;

    private FinancialYear $year;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->area = ThematicArea::factory()->create();
        $this->year = FinancialYear::factory()->started()->create(['is_current' => true]);
        $this->manager = User::factory()->create();
        $this->manager->assignRole('Thematic Manager');
        $this->area->users()->attach($this->manager->id, ['is_active' => true]);
    }

    private function savePlan(Indicator $indicator, array $payload, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->manager)->postJson(
            route('plan-builder.plans.save', [$this->area, $indicator]),
            ['financial_year_id' => $this->year->id] + $payload,
        );
    }

    public function test_grid_renders_one_row_per_indicator_with_its_interventions(): void
    {
        $indicator = Indicator::factory()->create(['thematic_area_id' => $this->area->id, 'name' => 'Women trained']);
        $intervention = Intervention::factory()->create(['thematic_area_id' => $this->area->id, 'name' => 'Run training workshops']);
        $intervention->indicators()->attach($indicator->id);

        $response = $this->actingAs($this->manager)->get(route('plan-builder.show', $this->area));

        $response->assertOk();
        $response->assertSee('pb-sheet', false);
        $response->assertSee('Women trained');
        $response->assertSee('Run training workshops');
    }

    public function test_a_half_filled_row_autosaves_as_a_draft(): void
    {
        $indicator = Indicator::factory()->create(['thematic_area_id' => $this->area->id]);

        $this->savePlan($indicator, ['annual_target' => 500])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.missing.0', fn ($value) => is_string($value));

        $this->assertDatabaseHas('indicator_plans', [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $this->year->id,
            'annual_target' => 500,
            'lead_organization_id' => null,
            'version' => 1,
        ]);

        $this->savePlan($indicator, ['annual_target' => 700])->assertOk();
        $this->assertSame(1, IndicatorPlan::query()->where('indicator_id', $indicator->id)->count());
        $this->assertSame(1, IndicatorPlan::query()->where('indicator_id', $indicator->id)->value('version'));
    }

    public function test_baseline_financial_year_sets_the_baseline_year(): void
    {
        $indicator = Indicator::factory()->create(['thematic_area_id' => $this->area->id]);

        $this->savePlan($indicator, ['baseline_financial_year_id' => $this->year->id, 'baseline_value' => 10])->assertOk();

        $this->assertSame(
            $this->year->start_date->year,
            IndicatorPlan::query()->where('indicator_id', $indicator->id)->value('baseline_year'),
        );
    }

    public function test_percentage_indicator_values_are_capped_at_one_hundred(): void
    {
        $percentage = MeasurementType::factory()->create(['code' => 'percentage', 'name' => 'Percentage']);
        $indicator = Indicator::factory()->create(['thematic_area_id' => $this->area->id, 'measurement_type_id' => $percentage->id]);

        $this->savePlan($indicator, ['annual_target' => 101])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('annual_target');

        $this->savePlan($indicator, ['annual_target' => 100])->assertOk();
    }

    public function test_user_without_access_to_the_thematic_area_cannot_save_a_row(): void
    {
        $indicator = Indicator::factory()->create(['thematic_area_id' => $this->area->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('Thematic Manager');

        $this->savePlan($indicator, ['annual_target' => 5], $outsider)->assertForbidden();
    }

    public function test_indicator_from_another_thematic_area_is_not_found(): void
    {
        $other = Indicator::factory()->create();

        $this->savePlan($other, ['annual_target' => 5])->assertNotFound();
    }

    public function test_submit_is_all_or_nothing_and_lists_incomplete_rows(): void
    {
        $organization = Organization::factory()->create();
        $complete = Indicator::factory()->create(['thematic_area_id' => $this->area->id, 'name' => 'Complete row']);
        $incomplete = Indicator::factory()->create(['thematic_area_id' => $this->area->id, 'name' => 'Incomplete row']);

        $this->savePlan($complete, [
            'baseline_financial_year_id' => $this->year->id, 'baseline_value' => 1, 'annual_target' => 10,
            'domestic_budget' => 1000, 'external_budget' => 0, 'lead_organization_id' => $organization->id,
        ])->assertOk();
        $this->savePlan($incomplete, ['annual_target' => 10])->assertOk();

        $this->actingAs($this->manager)
            ->postJson(route('plan-builder.plans.submit', $this->area), ['financial_year_id' => $this->year->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'Incomplete row') && ! str_contains($message, 'Complete row:'));

        $this->assertSame('draft', IndicatorPlan::query()->where('indicator_id', $complete->id)->value('status'));

        $this->savePlan($incomplete, [
            'baseline_financial_year_id' => $this->year->id, 'baseline_value' => 1,
            'domestic_budget' => 0, 'external_budget' => 0, 'lead_organization_id' => $organization->id,
        ])->assertOk();

        $this->actingAs($this->manager)
            ->postJson(route('plan-builder.plans.submit', $this->area), ['financial_year_id' => $this->year->id])
            ->assertOk()
            ->assertJsonPath('data.submitted', 2);

        $this->assertSame('pending_institution', IndicatorPlan::query()->where('indicator_id', $complete->id)->value('status'));
    }

    public function test_budget_is_not_required_when_the_indicator_has_no_budget_implication(): void
    {
        $organization = Organization::factory()->create();
        $indicator = Indicator::factory()->create(['thematic_area_id' => $this->area->id, 'has_budget_implication' => false]);

        $this->savePlan($indicator, [
            'baseline_financial_year_id' => $this->year->id, 'baseline_value' => 1, 'annual_target' => 10,
            'lead_organization_id' => $organization->id,
        ])->assertOk()->assertJsonPath('data.missing', []);
    }

    public function test_indicator_requiring_activity_needs_an_intervention_before_submit(): void
    {
        $organization = Organization::factory()->create();
        $indicator = Indicator::factory()->create(['thematic_area_id' => $this->area->id, 'requires_activity' => true, 'has_budget_implication' => false]);

        $payload = [
            'baseline_financial_year_id' => $this->year->id, 'baseline_value' => 1, 'annual_target' => 10,
            'lead_organization_id' => $organization->id,
        ];

        $this->savePlan($indicator, $payload)->assertOk()->assertJsonCount(1, 'data.missing');

        Intervention::factory()->create(['thematic_area_id' => $this->area->id])->indicators()->attach($indicator->id);

        $this->savePlan($indicator, $payload)->assertOk()->assertJsonPath('data.missing', []);
    }

    public function test_new_indicators_default_to_every_config_flag_on_except_location(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->postJson(route('indicators.store'), [
            'thematic_area_id' => $this->area->id,
            'code' => 'FLAG-DEFAULTS',
            'name' => 'Flag defaults',
        ])->assertCreated();

        $indicator = Indicator::query()->findOrFail($response->json('data.id'));

        $this->assertFalse((bool) $indicator->requires_location);
        $this->assertTrue((bool) $indicator->requires_activity);
        $this->assertTrue((bool) $indicator->has_budget_implication);
        $this->assertTrue((bool) $indicator->requires_evidence);
        $this->assertTrue((bool) $indicator->requires_hierarchical_approval);
    }
}
