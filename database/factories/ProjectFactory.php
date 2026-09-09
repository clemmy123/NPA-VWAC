<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('PRJ-????')),
            'name' => fake()->unique()->catchPhrase(),
            'description' => fake()->sentence(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYears(5)->toDateString(),
            'status' => 'active',
        ];
    }
}
