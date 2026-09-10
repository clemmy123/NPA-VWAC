<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\IndicatorBaseline;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorBaselinesPageTest extends TestCase
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

    public function test_authorized_user_sees_the_baselines_table_page(): void
    {
        $user = $this->userWithRole('Super Admin');
        $baseline = IndicatorBaseline::factory()->create();

        $response = $this->actingAs($user)->get(route('indicator-baselines.index'));

        $response->assertOk();
        $response->assertSee('table-card', false);
        $response->assertSee($baseline->indicator->name);
        $response->assertSee(route('indicator-baselines.create'), false);
    }

    public function test_unauthorized_user_is_forbidden_from_the_baselines_create_page(): void
    {
        $user = $this->userWithRole('Project Manager');

        $response = $this->actingAs($user)->get(route('indicator-baselines.create'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_create_a_baseline_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $indicator = Indicator::factory()->create();

        $response = $this->actingAs($user)->post(route('indicator-baselines.store'), [
            'indicator_id' => $indicator->id,
            'baseline_value' => 42.5,
        ]);

        $response->assertRedirect(route('indicator-baselines.index'));
        $this->assertDatabaseHas('indicator_baselines', ['indicator_id' => $indicator->id, 'baseline_value' => 42.5]);
    }

    public function test_authorized_user_can_update_a_baseline_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $baseline = IndicatorBaseline::factory()->create();

        $response = $this->actingAs($user)->put(route('indicator-baselines.update', $baseline), [
            'indicator_id' => $baseline->indicator_id,
            'baseline_value' => 99.9,
        ]);

        $response->assertRedirect(route('indicator-baselines.index'));
        $this->assertDatabaseHas('indicator_baselines', ['id' => $baseline->id, 'baseline_value' => 99.9]);
    }

    public function test_authorized_user_can_delete_a_baseline_via_the_web_form(): void
    {
        $user = $this->userWithRole('Super Admin');
        $baseline = IndicatorBaseline::factory()->create();

        $response = $this->actingAs($user)->delete(route('indicator-baselines.destroy', $baseline));

        $response->assertRedirect(route('indicator-baselines.index'));
        $this->assertDatabaseMissing('indicator_baselines', ['id' => $baseline->id]);
    }
}
