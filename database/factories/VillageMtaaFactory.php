<?php

namespace Database\Factories;

use App\Models\VillageMtaa;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VillageMtaa>
 */
class VillageMtaaFactory extends Factory
{
    protected $model = VillageMtaa::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('VM-????')),
            'name' => fake()->unique()->citySuffix().' Village',
            'type' => fake()->randomElement(['village', 'mtaa']),
            'ward_id' => Ward::factory(),
        ];
    }
}
