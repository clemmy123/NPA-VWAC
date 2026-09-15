<?php

namespace Database\Factories;

use App\Models\FinancialYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialYear>
 */
class FinancialYearFactory extends Factory
{
    protected $model = FinancialYear::class;

    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2020, 2035);

        return [
            'name' => "{$startYear}/".($startYear + 1),
            'start_date' => "{$startYear}-07-01",
            'end_date' => ($startYear + 1).'-06-30',
            'is_current' => false,
            'is_active' => true,
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }

    /**
     * Pins the year to one that has already started, for tests exercising
     * behavior that's gated on "not a future financial year".
     */
    public function started(): static
    {
        $startYear = now()->subYear()->year;

        return $this->state(fn (array $attributes) => [
            'name' => "{$startYear}/".($startYear + 1),
            'start_date' => "{$startYear}-07-01",
            'end_date' => ($startYear + 1).'-06-30',
        ]);
    }
}
