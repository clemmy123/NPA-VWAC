<?php

namespace Database\Factories;

use App\Models\Dimension;
use App\Models\Indicator;
use App\Models\IndicatorDimension;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndicatorDimension>
 */
class IndicatorDimensionFactory extends Factory
{
    protected $model = IndicatorDimension::class;

    public function definition(): array
    {
        return [
            'indicator_id' => Indicator::factory(),
            'dimension_id' => Dimension::factory(),
            'is_required' => false,
            'must_reconcile' => false,
        ];
    }

    public function mustReconcile(): static
    {
        return $this->state(fn (array $attributes) => [
            'must_reconcile' => true,
        ]);
    }
}
