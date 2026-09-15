<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\Project;
use App\Models\ThematicArea;
use Database\Seeders\ConstantDataSeeder;
use Database\Seeders\NpaVawcPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NpaVawcPlanSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_project_thematic_areas_and_indicators(): void
    {
        $this->seed(ConstantDataSeeder::class);
        $this->seed(NpaVawcPlanSeeder::class);

        $project = Project::where('code', 'NPA-VAWC')->first();

        $this->assertNotNull($project);
        $this->assertSame(9, ThematicArea::where('project_id', $project->id)->count());
        $this->assertSame(107, Indicator::whereHas('thematicArea', fn ($query) => $query->where('project_id', $project->id))->count());

        $indicator = Indicator::where('code', 'T1-01')->first();
        $this->assertNotNull($indicator);
        $this->assertNotNull($indicator->measurement_type_id);
        $this->assertNotNull($indicator->unit_of_measure_id);
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        $this->seed(ConstantDataSeeder::class);
        $this->seed(NpaVawcPlanSeeder::class);
        $this->seed(NpaVawcPlanSeeder::class);

        $project = Project::where('code', 'NPA-VAWC')->first();

        $this->assertSame(1, Project::where('code', 'NPA-VAWC')->count());
        $this->assertSame(9, ThematicArea::where('project_id', $project->id)->count());
        $this->assertSame(107, Indicator::whereHas('thematicArea', fn ($query) => $query->where('project_id', $project->id))->count());
    }
}
