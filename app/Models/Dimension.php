<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dimension extends Model
{
    use HasFactory;

    protected $fillable = [


        'code',


        'name',


        'is_active',


    ];

    protected function casts(): array
    {
        return [

            'is_active' => 'boolean',

        ];
    }

    public function options()


    {


        return $this->hasMany(DimensionOption::class);


    }
}
