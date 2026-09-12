<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorBaseline extends Model
{
    use HasFactory;

    protected $fillable = [

        'indicator_id',

        'financial_year_id',

        'baseline_value',

        'baseline_date',

        'organization_id',

        'remarks',

        'created_by',

    ];

    protected function casts(): array
    {
        return [

            'baseline_value' => 'decimal:4',

            'baseline_date' => 'date',

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

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
