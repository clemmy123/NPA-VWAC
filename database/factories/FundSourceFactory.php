<?php

namespace Database\Factories;

use App\Models\FundSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundSource>
 */
class FundSourceFactory extends Factory
{
    protected $model = FundSource::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('FS-????')),
            'name' => fake()->unique()->company().' Fund',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
