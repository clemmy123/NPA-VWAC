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

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id', 'division_id');
    }

    public function wards()
    {
        return $this->hasMany(Ward::class, 'township_id', 'township_id');
    }
}
