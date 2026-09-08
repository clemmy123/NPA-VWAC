<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Township extends Model
{
    use HasFactory;

    protected $table = 'township';

    protected $primaryKey = 'township_id';

    protected $fillable = [


        'code',


        'name',


        'division_id',


    ];


}
