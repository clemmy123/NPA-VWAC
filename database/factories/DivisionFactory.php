<?php

namespace Database\Factories;

use App\Models\Council;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
{
    protected $model = Division::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('DV-????')),
            'name' => fake()->unique()->citySuffix().' Division',
            'council_id' => Council::factory(),
        ];
    }
}
