<?php

namespace Database\Factories;

use App\Models\FinancialYear;
use App\Models\ReportingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportingPeriod>
 */
class ReportingPeriodFactory extends Factory
{
    protected $model = ReportingPeriod::class;

    public function definition(): array
    {
        $sequence = fake()->numberBetween(1, 4);

        return [
            'financial_year_id' => FinancialYear::factory(),
            'code' => "Q{$sequence}",
            'name' => "Quarter {$sequence}",
            'period_type' => 'quarter',
            'sequence' => $sequence,
            'start_date' => now()->startOfYear()->addMonths(($sequence - 1) * 3),
            'end_date' => now()->startOfYear()->addMonths($sequence * 3)->subDay(),
            'is_active' => true,
        ];
    }
}
