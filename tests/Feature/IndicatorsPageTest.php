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

        $response = $this->actingAs($user)->get(route('indicators.index'));

        $response->assertOk();
        $response->assertSee('table-card', false);
        $response->assertSee($indicator->name);
        $response->assertSee($indicator->code);
        $response->assertSee(route('indicators.create'), false);
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

    public function test_authorized_user_can_delete_an_indicator_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create();

        $response = $this->actingAs($user)->delete(route('indicators.destroy', $indicator));

        $response->assertRedirect(route('indicators.index'));
        $this->assertSoftDeleted($indicator);
    }
}
