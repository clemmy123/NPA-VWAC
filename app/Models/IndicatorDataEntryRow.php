<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorDataEntryRow extends Model
{
    use HasFactory;

    protected $fillable = ['indicator_data_entry_id', 'label', 'value'];

    protected function casts(): array
    {
        return ['value' => 'decimal:4'];
    }


}
