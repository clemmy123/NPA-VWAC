<?php

namespace Database\Factories;

use App\Models\IndicatorDataEntry;
use App\Models\IndicatorDataReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndicatorDataReview>
 */
class IndicatorDataReviewFactory extends Factory
{
    protected $model = IndicatorDataReview::class;

    public function definition(): array
    {
        return [
            'indicator_data_entry_id' => IndicatorDataEntry::factory(),
            'reviewed_by' => User::factory(),
            'action' => 'approved',
            'comment' => fake()->sentence(),
        ];
    }
}
