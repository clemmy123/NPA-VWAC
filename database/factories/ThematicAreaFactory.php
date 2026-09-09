<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ThematicArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThematicArea>
 */
class ThematicAreaFactory extends Factory
{
    protected $model = ThematicArea::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}
