<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorDimension extends Model
{
    use HasFactory;

    protected $fillable = [

        'indicator_id',

        'dimension_id',

        'is_required',

        'must_reconcile',

    ];

    protected function casts(): array
    {
        return [

            'is_required' => 'boolean',

            'must_reconcile' => 'boolean',

        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(Dimension::class);
    }
}
