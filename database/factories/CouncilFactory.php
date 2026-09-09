<?php

namespace Database\Factories;

use App\Models\Council;
use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Council>
 */
class CouncilFactory extends Factory
{
    protected $model = Council::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('CL-????')),
            'name' => fake()->unique()->city(),
            'district_id' => District::factory(),
        ];
    }
}
