<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorBaseline extends Model
{
    use HasFactory;

    protected $fillable = ['indicator_id', 'financial_year_id', 'baseline_value', 'baseline_date', 'source', 'remarks', 'created_by'];

    protected function casts(): array
    {
        return ['baseline_value' => 'decimal:4', 'baseline_date' => 'date'];
    }


}
