<?php

namespace Database\Factories;

use App\Models\IndicatorDataEntry;
use App\Models\IndicatorDataEntryExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndicatorDataEntryExpense>
 */
class IndicatorDataEntryExpenseFactory extends Factory
{
    protected $model = IndicatorDataEntryExpense::class;

    public function definition(): array
    {
        return [
            'indicator_data_entry_id' => IndicatorDataEntry::factory(),
            'expense_category' => fake()->word(),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 0, 5000),
            'currency' => 'TZS',
        ];
    }
}
