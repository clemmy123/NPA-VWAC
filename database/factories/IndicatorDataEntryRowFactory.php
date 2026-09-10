<?php

namespace Database\Factories;

use App\Models\IndicatorDataEntry;
use App\Models\IndicatorDataEntryRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndicatorDataEntryRow>
 */
class IndicatorDataEntryRowFactory extends Factory
{
    protected $model = IndicatorDataEntryRow::class;

    public function definition(): array
    {
        return [
            'indicator_data_entry_id' => IndicatorDataEntry::factory(),
            'label' => fake()->word(),
            'value' => fake()->randomFloat(4, 0, 1000),
        ];
    }
}
