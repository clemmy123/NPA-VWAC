<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Indicator extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [

        'thematic_area_id',

        'code',

        'name',

        'description',

        'measurement_type_id',

        'unit_of_measure_id',

        'collection_mode',

        'aggregation_method',

        'reporting_frequency',

        'collection_scope',

        'requires_location',

        'reporting_location_level',

        'requires_activity',

        'has_budget_implication',

        'requires_evidence',

        'requires_hierarchical_approval',

        'status',

        'created_by',

    ];

    protected function casts(): array
    {
        return [

            'requires_location' => 'boolean',

            'requires_activity' => 'boolean',

            'has_budget_implication' => 'boolean',

            'requires_evidence' => 'boolean',

            'requires_hierarchical_approval' => 'boolean',

        ];
    }

    public function plans()
    {
        return $this->hasMany(IndicatorPlan::class);
    }

    public function thematicArea()
    {

        return $this->belongsTo(ThematicArea::class);

    }

    public function measurementType()
    {

        return $this->belongsTo(MeasurementType::class);

    }

    public function unitOfMeasure()
    {

        return $this->belongsTo(UnitOfMeasure::class);

    }

    public function baselines()
    {

        return $this->hasMany(IndicatorBaseline::class);

    }

    public function targets()
    {

        return $this->hasMany(IndicatorTarget::class);

    }

    public function entries()
    {

        return $this->hasMany(IndicatorDataEntry::class);

    }

    public function assignments()
    {

        return $this->hasMany(IndicatorDataAssignment::class);

    }

    public function dimensions()
    {

        return $this->belongsToMany(Dimension::class, 'indicator_dimensions')->withPivot(['is_required', 'must_reconcile'])->withTimestamps();

    }

    public function interventions()
    {

        return $this->belongsToMany(Intervention::class, 'indicator_interventions')->withTimestamps();

    }

    public function fundSources()
    {

        return $this->belongsToMany(FundSource::class, 'indicator_fund_sources')->withPivot('is_primary')->withTimestamps();

    }

    /**
     * The strictest decimals/ceiling implied by the measurement type and the
     * unit together — "Count" of "People" is whole numbers, a "Decimal" in
     * "Percent" is capped at 100. A null decimals means no restriction.
     *
     * @return array{decimals: int|null, max: int|null}
     */
    public function valueLimits(): array
    {
        $limits = array_filter([$this->measurementType?->valueLimits(), $this->unitOfMeasure?->valueLimits()]);
        $decimals = array_filter(array_column($limits, 'decimals'), fn ($value) => $value !== null);
        $maxima = array_filter(array_column($limits, 'max'), fn ($value) => $value !== null);

        return [
            'decimals' => $decimals === [] ? null : min($decimals),
            'max' => $maxima === [] ? null : min($maxima),
        ];
    }
}
