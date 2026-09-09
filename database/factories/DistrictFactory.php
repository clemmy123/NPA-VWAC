<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    protected $model = District::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('DS-????')),
            'name' => fake()->unique()->city(),
            'region_id' => Region::factory(),
        ];
    }
}
