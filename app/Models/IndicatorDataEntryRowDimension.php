<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorDataEntryRowDimension extends Model
{
    use HasFactory;

    protected $fillable = [
        'indicator_data_entry_row_id',
        'dimension_option_id',
    ];

    public function row(): BelongsTo
    {
        return $this->belongsTo(IndicatorDataEntryRow::class, 'indicator_data_entry_row_id');
    }

    public function dimensionOption(): BelongsTo
    {
        return $this->belongsTo(DimensionOption::class);
    }
}
