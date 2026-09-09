<?php

namespace Database\Factories;

use App\Models\Intervention;
use App\Models\ThematicArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intervention>
 */
class InterventionFactory extends Factory
{
    protected $model = Intervention::class;

    public function definition(): array
    {
        return [
            'thematic_area_id' => ThematicArea::factory(),
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}
