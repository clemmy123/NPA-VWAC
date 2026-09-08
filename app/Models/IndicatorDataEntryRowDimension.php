<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorDataEntryRowDimension extends Model
{
    use HasFactory;

    protected $fillable = ['indicator_data_entry_row_id', 'dimension_option_id'];


}
