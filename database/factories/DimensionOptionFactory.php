<?php

namespace Database\Factories;

use App\Models\Dimension;
use App\Models\DimensionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DimensionOption>
 */
class DimensionOptionFactory extends Factory
{
    protected $model = DimensionOption::class;

    public function definition(): array
    {
        return [
            'dimension_id' => Dimension::factory(),
            'code' => strtoupper(fake()->unique()->lexify('OPT-????')),
            'name' => fake()->unique()->word(),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
