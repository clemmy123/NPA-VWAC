<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorsPageTest extends TestCase
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

    public function test_authorized_user_sees_the_indicators_table_page(): void
    {
        $user = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create(['name' => 'Number of cases reported', 'code' => 'IND-001']);

        $response = $this->actingAs($user)->get(route('indicators.index', [
            'project_id' => $indicator->thematicArea->project_id,
            'thematic_area_id' => $indicator->thematic_area_id,
        ]));

        $response->assertOk();
        $response->assertSee('table-card', false);
        $response->assertSee($indicator->name);
        $response->assertSee($indicator->code);
        $response->assertSee(route('indicators.create'), false);
    }

    public function test_indicators_are_hidden_until_project_and_thematic_area_are_selected(): void
    {
        $user = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create(['name' => 'Must be filtered']);

        $this->actingAs($user)->get(route('indicators.index'))
            ->assertOk()
            ->assertDontSee($indicator->name);
    }

    public function test_unauthorized_user_is_forbidden_from_the_indicators_page(): void
    {
        $user = $this->userWithRole('Project Manager');

        $response = $this->actingAs($user)->get(route('indicators.create'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_create_an_indicator_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $thematicArea = ThematicArea::factory()->create();

        $response = $this->actingAs($user)->post(route('indicators.store'), [
            'thematic_area_id' => $thematicArea->id,
            'code' => 'IND-NEW',
            'name' => 'Number of survivors supported',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('indicators.index'));
        $this->assertDatabaseHas('indicators', ['code' => 'IND-NEW']);
    }

    public function test_creating_an_indicator_with_blank_select_fields_falls_back_to_db_defaults(): void
    {
        // Regression test: the create form always submits collection_mode /
        // aggregation_method / reporting_frequency / collection_scope, even
        // when nothing is chosen (empty string). Laravel's global
        // ConvertEmptyStringsToNull middleware turns that into an explicit
        // null, which used to bypass the columns' NOT NULL DB defaults and
        // throw a QueryException. The form now pre-selects a default option
        // for these four fields so this can't happen from the UI, but this
        // guards the controller/request contract directly.
        $user = $this->userWithRole('Super Admin');
        $thematicArea = ThematicArea::factory()->create();

        $response = $this->actingAs($user)->post(route('indicators.store'), [
            'thematic_area_id' => $thematicArea->id,
            'code' => 'IND-BLANK',
            'name' => 'Blank select regression',
            'status' => 'active',
            'measurement_type_id' => '',
            'unit_of_measure_id' => '',
            'collection_mode' => '',
            'aggregation_method' => '',
            'reporting_frequency' => '',
            'collection_scope' => '',
            'reporting_location_level' => '',
        ]);

        $response->assertRedirect(route('indicators.index'));
        $this->assertDatabaseHas('indicators', [
            'code' => 'IND-BLANK',
            'collection_mode' => 'progressive',
            'aggregation_method' => 'sum',
            'reporting_frequency' => 'quarterly',
            'collection_scope' => 'national',
        ]);
    }

    public function test_authorized_user_can_update_an_indicator_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create();

        $response = $this->actingAs($user)->put(route('indicators.update', $indicator), [
            'thematic_area_id' => $indicator->thematic_area_id,
            'code' => $indicator->code,
            'name' => 'Renamed Indicator',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('indicators.index'));
        $this->assertDatabaseHas('indicators', ['id' => $indicator->id, 'name' => 'Renamed Indicator']);
    }

    public function test_edit_form_preserves_saved_indicator_settings(): void
    {
        $user = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create([
            'description' => 'Existing description',
            'collection_mode' => 'snapshot',
            'aggregation_method' => 'latest',
            'reporting_frequency' => 'monthly',
            'collection_scope' => 'geographic',
            'requires_location' => true,
            'reporting_location_level' => 'kitongoji',
            'requires_activity' => true,
            'has_budget_implication' => true,
            'requires_evidence' => true,
            'requires_hierarchical_approval' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('indicators.edit', $indicator));

        $response->assertOk();
        $response->assertSee('value="'.$indicator->thematicArea->project_id.'" selected', false);
        $response->assertSee('value="'.$indicator->thematic_area_id.'" data-project-id="'.$indicator->thematicArea->project_id.'" selected', false);
        $response->assertSee('Existing description');
        $response->assertSee('value="snapshot" selected', false);
        $response->assertSee('value="latest" selected', false);
        $response->assertSee('value="monthly" selected', false);
        $response->assertSee('value="geographic" selected', false);
        $response->assertSee('value="kitongoji" selected', false);
        $response->assertSee('name="requires_location"', false);
        $response->assertSee('name="requires_hierarchical_approval"', false);
        $response->assertSee('var savedThematicId = "'.$indicator->thematic_area_id.'";', false);
    }

    public function test_authorized_user_can_disable_an_indicator_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('indicators.index'))
            ->patch(route('indicators.disable', $indicator));

        $response->assertRedirect(route('indicators.index'));
        $this->assertDatabaseHas('indicators', ['id' => $indicator->id, 'status' => 'inactive']);
    }
}
