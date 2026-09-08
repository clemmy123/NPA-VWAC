<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Council extends Model
{
    use HasFactory;

    protected $table = 'council';

    protected $primaryKey = 'council_id';

    protected $fillable = [


        'code',


        'name',


        'district_id',


    ];


}
