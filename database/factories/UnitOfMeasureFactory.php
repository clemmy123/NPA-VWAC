<?php

namespace Database\Factories;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitOfMeasure>
 */
class UnitOfMeasureFactory extends Factory
{
    protected $model = UnitOfMeasure::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('UOM-????')),
            'name' => fake()->unique()->word(),
            'symbol' => fake()->lexify('??'),
            'is_active' => true,
        ];
    }
}
