<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorTarget;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorTargetsPageTest extends TestCase
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

    public function test_authorized_user_sees_the_targets_table_page(): void
    {
        $user = $this->userWithRole('Super Admin');
        $target = IndicatorTarget::factory()->create();

        $response = $this->actingAs($user)->get(route('indicator-targets.index'));

        $response->assertOk();
        $response->assertSee('table-card', false);
        $response->assertSee($target->indicator->name);
        $response->assertSee(route('indicator-targets.create'), false);
    }

    public function test_unauthorized_user_is_forbidden_from_the_targets_create_page(): void
    {
        $user = $this->userWithRole('Project Manager');

        $response = $this->actingAs($user)->get(route('indicator-targets.create'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_create_a_target_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->create();

        $response = $this->actingAs($user)->post(route('indicator-targets.store'), [
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'target_value' => 500,
        ]);

        $response->assertRedirect(route('indicator-targets.index'));
        $this->assertDatabaseHas('indicator_targets', ['indicator_id' => $indicator->id, 'target_value' => 500]);
    }

    public function test_authorized_user_can_update_a_target_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $target = IndicatorTarget::factory()->create();

        $response = $this->actingAs($user)->put(route('indicator-targets.update', $target), [
            'indicator_id' => $target->indicator_id,
            'financial_year_id' => $target->financial_year_id,
            'target_value' => 777,
        ]);

        $response->assertRedirect(route('indicator-targets.index'));
        $this->assertDatabaseHas('indicator_targets', ['id' => $target->id, 'target_value' => 777]);
    }

    public function test_authorized_user_can_delete_a_target_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $target = IndicatorTarget::factory()->create();

        $response = $this->actingAs($user)->delete(route('indicator-targets.destroy', $target));

        $response->assertRedirect(route('indicator-targets.index'));
        $this->assertDatabaseMissing('indicator_targets', ['id' => $target->id]);
    }
}
