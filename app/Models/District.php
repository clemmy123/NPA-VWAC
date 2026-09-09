<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $table = 'district';

    protected $primaryKey = 'district_id';

    protected $fillable = [

        'code',

        'name',

        'region_id',

    ];

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'region_id');
    }

    public function councils()
    {
        return $this->hasMany(Council::class, 'district_id', 'district_id');
    }
}
