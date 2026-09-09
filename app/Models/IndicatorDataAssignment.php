<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorDataAssignment extends Model
{
    use HasFactory;

    protected $fillable = [

        'indicator_id',

        'user_id',

        'location_level',

        'location_id',

        'organization_id',

        'data_source_id',

        'is_active',

    ];

    protected function casts(): array
    {
        return [

            'is_active' => 'boolean',

        ];
    }
}
