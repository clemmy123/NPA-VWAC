<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VillageMtaa extends Model
{
    use HasFactory;

    protected $table = 'village_mtaa';

    protected $primaryKey = 'village_mtaa_id';

    protected $fillable = ['code', 'name', 'type', 'ward_id'];


}
