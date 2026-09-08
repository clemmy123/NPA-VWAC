<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorDataReview extends Model
{
    use HasFactory;

    protected $fillable = ['indicator_data_entry_id', 'reviewed_by', 'action', 'comment'];


}
