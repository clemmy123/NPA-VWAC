<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorDataEntryExpense extends Model
{
    use HasFactory;

    protected $fillable = [

        'indicator_data_entry_id',

        'expense_category',

        'description',

        'amount',

        'currency',

    ];

    protected function casts(): array
    {
        return [

            'amount' => 'decimal:2',

        ];
    }

    public function entry()
    {
        return $this->belongsTo(IndicatorDataEntry::class, 'indicator_data_entry_id');
    }
}
