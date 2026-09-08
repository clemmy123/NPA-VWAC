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


        'region_id',


        'district_id',


        'council_id',


        'division_id',


        'township_id',


        'ward_id',


        'village_mtaa_id',


        'kitongoji_id',


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
