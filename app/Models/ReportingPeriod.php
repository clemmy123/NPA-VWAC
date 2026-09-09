<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingPeriod extends Model
{
    use HasFactory;

    protected $fillable = [

        'financial_year_id',

        'code',

        'name',

        'period_type',

        'sequence',

        'start_date',

        'end_date',

        'is_active',

    ];

    protected function casts(): array
    {
        return [

            'start_date' => 'date',

            'end_date' => 'date',

            'is_active' => 'boolean',

        ];
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }
}
