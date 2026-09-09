<?php

namespace Database\Factories;

use App\Models\MeasurementType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeasurementType>
 */
class MeasurementTypeFactory extends Factory
{
    protected $model = MeasurementType::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('MT-????')),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
