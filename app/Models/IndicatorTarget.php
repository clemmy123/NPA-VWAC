<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorTarget extends Model
{
    use HasFactory;

    protected $fillable = [

        'indicator_id',

        'financial_year_id',

        'reporting_period_id',

        'dimension_option_id',

        'target_value',

        'remarks',

        'created_by',

    ];

    protected function casts(): array
    {
        return [

            'target_value' => 'decimal:4',

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

    public function dimensionOption()
    {
        return $this->belongsTo(DimensionOption::class);
    }
}
