<?php

namespace Database\Factories;

use App\Models\Indicator;
use App\Models\MeasurementType;
use App\Models\ThematicArea;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Indicator>
 */
class IndicatorFactory extends Factory
{
    protected $model = Indicator::class;

    public function definition(): array
    {
        return [
            'thematic_area_id' => ThematicArea::factory(),
            'code' => strtoupper(fake()->unique()->lexify('IND-????')),
            'name' => fake()->unique()->sentence(4),
            'description' => fake()->sentence(),
            'measurement_type_id' => MeasurementType::factory(),
            'unit_of_measure_id' => UnitOfMeasure::factory(),
            'collection_mode' => 'progressive',
            'aggregation_method' => 'sum',
            'reporting_frequency' => 'quarterly',
            'collection_scope' => 'national',
            'requires_location' => false,
            'reporting_location_level' => null,
            'requires_activity' => false,
            'has_budget_implication' => false,
            'requires_evidence' => false,
            'status' => 'draft',
        ];
    }
}
