<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\Township;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ward>
 */
class WardFactory extends Factory
{
    protected $model = Ward::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('WD-????')),
            'name' => fake()->unique()->citySuffix().' Ward',
            'division_id' => Division::factory(),
            'township_id' => null,
        ];
    }

    public function withTownship(): static
    {
        return $this->state(function () {
            $township = Township::factory()->create();

            return [
                'division_id' => $township->division_id,
                'township_id' => $township->township_id,
            ];
        });
    }
}
