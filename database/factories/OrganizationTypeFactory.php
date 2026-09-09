<?php

namespace Database\Factories;

use App\Models\OrganizationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationType>
 */
class OrganizationTypeFactory extends Factory
{
    protected $model = OrganizationType::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('OT-????')),
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }
}
