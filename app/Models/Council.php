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

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    public function divisions()
    {
        return $this->hasMany(Division::class, 'council_id', 'council_id');
    }
}
