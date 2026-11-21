<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\ThematicArea;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerCollectionScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_manager_collects_only_in_areas_flagged_for_collection(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Thematic Manager');
        $collecting = ThematicArea::factory()->create();
        $managedOnly = ThematicArea::factory()->create();
        $inCollecting = Indicator::factory()->create(['thematic_area_id' => $collecting->id, 'status' => 'active']);
        $inManagedOnly = Indicator::factory()->create(['thematic_area_id' => $managedOnly->id, 'status' => 'active']);
        $manager->thematicAreas()->attach($collecting->id, ['is_active' => true, 'can_collect' => true]);
        $manager->thematicAreas()->attach($managedOnly->id, ['is_active' => true, 'can_collect' => false]);

        $ids = $manager->assignedIndicatorIds();

        $this->assertContains($inCollecting->id, $ids);
        $this->assertNotContains($inManagedOnly->id, $ids);
    }

    public function test_a_project_manager_can_assign_themselves_to_collect_in_an_area(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Project Manager');
        $area = ThematicArea::factory()->create();
        $manager->projects()->attach($area->project_id, ['is_active' => true]);

        $this->actingAs($manager)->post(route('thematic-areas.managers.store', $area), [
            'user_id' => $manager->id,
            'can_collect' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('thematic_area_users', [
            'thematic_area_id' => $area->id, 'user_id' => $manager->id, 'can_collect' => true,
        ]);
    }
}
