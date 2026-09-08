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


    ];

    protected function casts(): array
    {
        return [

            'amount' => 'decimal:2',

        ];
    }


}
