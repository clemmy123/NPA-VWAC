<?php

namespace Database\Factories;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndicatorTarget>
 */
class IndicatorTargetFactory extends Factory
{
    protected $model = IndicatorTarget::class;

    public function definition(): array
    {
        return [
            'indicator_id' => Indicator::factory(),
            'financial_year_id' => FinancialYear::factory(),
            'reporting_period_id' => null,
            'dimension_option_id' => null,
            'target_value' => fake()->randomFloat(4, 0, 100000),
            'remarks' => fake()->sentence(),
        ];
    }
}
