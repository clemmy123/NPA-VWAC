<?php

namespace Database\Factories;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndicatorDataEntry>
 */
class IndicatorDataEntryFactory extends Factory
{
    protected $model = IndicatorDataEntry::class;

    public function definition(): array
    {
        return [
            'indicator_id' => Indicator::factory(),
            'financial_year_id' => FinancialYear::factory()->started(),
            'reporting_period_id' => null,
            'entry_date' => now()->toDateString(),
            'activity_name' => fake()->sentence(3),
            'location_level' => null,
            'location_id' => null,
            'actual_value' => fake()->randomFloat(4, 0, 10000),
            'currency' => 'TZS',
            'source_type' => 'manual',
            'entered_by' => User::factory(),
            'status' => 'draft',
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);
    }
}
