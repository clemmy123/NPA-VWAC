<?php

namespace Database\Factories;

use App\Models\DataSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataSource>
 */
class DataSourceFactory extends Factory
{
    protected $model = DataSource::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('DSRC-????')),
            'name' => fake()->unique()->company(),
            'description' => fake()->sentence(),
            'collection_method' => fake()->randomElement(['manual', 'integration']),
            'is_active' => true,
        ];
    }
}
