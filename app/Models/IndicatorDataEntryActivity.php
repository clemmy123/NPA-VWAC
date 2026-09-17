<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorDataEntryActivity extends Model
{
    protected $fillable = ['name', 'description', 'participants_total', 'women', 'men', 'children', 'other'];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(IndicatorDataEntry::class, 'indicator_data_entry_id');
    }
}
