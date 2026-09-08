<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    use HasFactory;

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

        ];
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
    public function dataSources()

    {

        return $this->belongsToMany(DataSource::class,'indicator_data_sources')->withPivot('is_primary')->withTimestamps();

    }
    public function dimensions()

    {

        return $this->belongsToMany(Dimension::class,'indicator_dimensions')->withPivot(['is_required','must_reconcile'])->withTimestamps();

    }
}
