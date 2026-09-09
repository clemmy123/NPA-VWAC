<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $table = 'division';

    protected $primaryKey = 'division_id';

    protected $fillable = [

        'code',

        'name',

        'council_id',

    ];

    public function council()
    {
        return $this->belongsTo(Council::class, 'council_id', 'council_id');
    }

    public function townships()
    {
        return $this->hasMany(Township::class, 'division_id', 'division_id');
    }

    public function wards()
    {
        return $this->hasMany(Ward::class, 'division_id', 'division_id');
    }
}
