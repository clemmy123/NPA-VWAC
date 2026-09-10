<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorDataReview extends Model
{
    use HasFactory;

    protected $fillable = [

        'indicator_data_entry_id',

        'reviewed_by',

        'action',

        'comment',

    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(IndicatorDataEntry::class, 'indicator_data_entry_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
