<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $table = 'ward';

    protected $primaryKey = 'ward_id';

    protected $fillable = [

        'code',

        'name',

        'division_id',

        'township_id',

    ];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id', 'division_id');
    }

    public function township()
    {
        return $this->belongsTo(Township::class, 'township_id', 'township_id');
    }

    public function villageMtaas()
    {
        return $this->hasMany(VillageMtaa::class, 'ward_id', 'ward_id');
    }
}
