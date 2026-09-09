<?php

namespace Database\Factories;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorBaseline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndicatorBaseline>
 */
class IndicatorBaselineFactory extends Factory
{
    protected $model = IndicatorBaseline::class;

    public function definition(): array
    {
        return [
            'indicator_id' => Indicator::factory(),
            'financial_year_id' => FinancialYear::factory(),
            'baseline_value' => fake()->randomFloat(4, 0, 100000),
            'baseline_date' => now()->toDateString(),
            'source' => fake()->company(),
            'remarks' => fake()->sentence(),
        ];
    }
}
