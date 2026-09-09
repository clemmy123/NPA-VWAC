<?php

namespace Database\Factories;

use App\Models\Dimension;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dimension>
 */
class DimensionFactory extends Factory
{
    protected $model = Dimension::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('DIM-????')),
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }
}
