<?php

namespace Database\Factories;

use App\Models\Kitongoji;
use App\Models\VillageMtaa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kitongoji>
 */
class KitongojiFactory extends Factory
{
    protected $model = Kitongoji::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('KT-????')),
            'name' => fake()->unique()->citySuffix().' Kitongoji',
            'village_mtaa_id' => VillageMtaa::factory(),
        ];
    }
}
