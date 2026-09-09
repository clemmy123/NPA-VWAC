<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThematicAreaUser extends Model
{
    use HasFactory;

    protected $fillable = [

        'thematic_area_id',

        'user_id',

        'is_active',

    ];

    protected function casts(): array
    {
        return [

            'is_active' => 'boolean',

        ];
    }
}
