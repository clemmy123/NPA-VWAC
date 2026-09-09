<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kitongoji extends Model
{
    use HasFactory;

    protected $table = 'kitongoji';

    protected $primaryKey = 'kitongoji_id';

    protected $fillable = [

        'code',

        'name',

        'village_mtaa_id',

    ];

    public function villageMtaa()
    {
        return $this->belongsTo(VillageMtaa::class, 'village_mtaa_id', 'village_mtaa_id');
    }
}
