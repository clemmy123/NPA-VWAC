<?php

namespace Database\Factories;

use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndicatorDataAssignment>
 */
class IndicatorDataAssignmentFactory extends Factory
{
    protected $model = IndicatorDataAssignment::class;

    public function definition(): array
    {
        return [
            'indicator_id' => Indicator::factory(),
            'user_id' => User::factory(),
            'location_level' => null,
            'location_id' => null,
            'organization_id' => null,
            'data_source_id' => null,
            'is_active' => true,
        ];
    }

    public function forLocation(string $level, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'location_level' => $level,
            'location_id' => $id,
        ]);
    }
}
