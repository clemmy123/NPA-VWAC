<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class IndicatorDataEntryRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'indicator_data_entry_id',
        'label',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(IndicatorDataEntry::class, 'indicator_data_entry_id');
    }

    public function dimensionOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            DimensionOption::class,
            'indicator_data_entry_row_dimensions',
            'indicator_data_entry_row_id',
            'dimension_option_id',
        )->withTimestamps();
    }
}
