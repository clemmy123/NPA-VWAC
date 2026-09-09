<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\Township;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Township>
 */
class TownshipFactory extends Factory
{
    protected $model = Township::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('TW-????')),
            'name' => fake()->unique()->citySuffix().' Township',
            'division_id' => Division::factory(),
        ];
    }
}
