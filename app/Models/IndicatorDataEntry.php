<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorDataEntry extends Model
{
    use HasFactory;

    protected $fillable = [


        'reference_no',


        'indicator_id',


        'financial_year_id',


        'reporting_period_id',


        'entry_date',


        'activity_name',


        'activity_description',


        'region_id',


        'district_id',


        'council_id',


        'division_id',


        'township_id',


        'ward_id',


        'village_mtaa_id',


        'kitongoji_id',


        'organization_id',


        'data_source_id',


        'actual_value',


        'budget_allocated',


        'budget_used',


        'remarks',


        'entered_by',


        'submitted_at',


        'approved_at',


        'approved_by',


        'status',


    ];

    protected function casts(): array
    {
        return [

            'entry_date' => 'date',

            'actual_value' => 'decimal:4',

            'budget_allocated' => 'decimal:2',

            'budget_used' => 'decimal:2',

            'submitted_at' => 'datetime',

            'approved_at' => 'datetime',

        ];
    }

    public function indicator()


    {


        return $this->belongsTo(Indicator::class);


    }
    public function financialYear()

    {

        return $this->belongsTo(FinancialYear::class);

    }
    public function reportingPeriod()

    {

        return $this->belongsTo(ReportingPeriod::class);

    }
    public function rows()

    {

        return $this->hasMany(IndicatorDataEntryRow::class);

    }
    public function expenses()

    {

        return $this->hasMany(IndicatorDataEntryExpense::class);

    }
    public function reviews()

    {

        return $this->hasMany(IndicatorDataReview::class);

    }
}
