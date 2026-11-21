<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorPlan;
use App\Models\Organization;
use App\Models\ThematicArea;
use App\Models\User;
use App\Services\IndicatorDataEntryService;
use App\Services\IndicatorPlanService;
use App\Services\IndicatorReviewService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MtakuuwaRequirementsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function fixture(): array
    {
        $organization = Organization::factory()->create();
        $area = ThematicArea::factory()->create();
        $year = FinancialYear::factory()->started()->create(['is_current' => true]);
        $indicator = Indicator::factory()->create(['thematic_area_id' => $area->id, 'status' => 'active']);
        $thematic = User::factory()->create();
        $thematic->assignRole('Thematic Manager');
        $area->users()->attach($thematic->id, ['is_active' => true]);
        $institution = User::factory()->create(['organization_id' => $organization->id]);
        $institution->assignRole('Data Approver');

        return compact('organization', 'area', 'year', 'indicator', 'thematic', 'institution');
    }

    private function draftPlan(array $f): IndicatorPlan
    {
        return app(IndicatorPlanService::class)->save([
            'indicator_id' => $f['indicator']->id,
            'financial_year_id' => $f['year']->id,
            'planned_activity' => 'Train district social welfare officers',
            'baseline_year' => 2025,
            'baseline_value' => 10,
            'annual_target' => 100,
            'domestic_budget' => 4000000,
            'external_budget' => 1500000,
            'lead_organization_id' => $f['organization']->id,
            'participating_organization_ids' => [],
        ], $f['thematic']);
    }

    public function test_completing_the_plan_opens_it_for_collection_without_an_institution_step(): void
    {
        $f = $this->fixture();
        $plan = $this->draftPlan($f);
        $service = app(IndicatorPlanService::class);
        $this->assertSame('draft', $plan->status);
        $plan = $service->transition($plan, $f['thematic'], 'submit', null);
        $this->assertSame('approved', $plan->status);
        $this->assertEquals(5500000, (float) $plan->domestic_budget + (float) $plan->external_budget);

        $plan = $service->transition($plan, $f['thematic'], 'unlock', null);
        $this->assertSame('returned', $plan->status);
    }

    public function test_unapproved_annual_plan_blocks_new_collection(): void
    {
        $f = $this->fixture();
        $this->draftPlan($f);
        $this->expectException(ValidationException::class);
        app(IndicatorDataEntryService::class)->create([
            'indicator_id' => $f['indicator']->id,
            'financial_year_id' => $f['year']->id,
            'entry_date' => now()->toDateString(),
        ], [], [], [], $f['thematic']);
    }

    public function test_approved_plan_is_immutable_and_collection_stores_snapshot(): void
    {
        $f = $this->fixture();
        $service = app(IndicatorPlanService::class);
        $plan = $service->transition($this->draftPlan($f), $f['thematic'], 'submit', null);
        $entry = app(IndicatorDataEntryService::class)->create([
            'indicator_id' => $f['indicator']->id,
            'financial_year_id' => $f['year']->id,
            'entry_date' => now()->toDateString(),
            'organization_id' => $f['organization']->id,
            'actual_value' => 20,
        ], [], [], [], $f['thematic']);
        $this->assertSame($plan->id, $entry->indicator_plan_id);
        $this->assertEquals(5500000, (float) $entry->budget_allocated);
        $this->assertSame('Train district social welfare officers', $entry->plan_snapshot['planned_activity']);
        $this->expectException(ValidationException::class);
        $this->draftPlan($f);
    }

    public function test_another_institution_cannot_confirm_a_submission(): void
    {
        $f = $this->fixture();
        $entry = IndicatorDataEntry::factory()->submitted()->create([
            'indicator_id' => $f['indicator']->id, 'organization_id' => $f['organization']->id,
        ]);
        $outsider = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);
        $outsider->assignRole('Data Approver');
        $this->expectException(HttpException::class);
        app(IndicatorReviewService::class)->confirmInstitution($entry, $outsider);
    }
}
