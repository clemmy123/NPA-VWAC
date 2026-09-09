<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $table = 'region';

    protected $primaryKey = 'region_id';

    protected $fillable = [

        'code',

        'name',

    ];

    public function districts()
    {
        return $this->hasMany(District::class, 'region_id', 'region_id');
    }
}
