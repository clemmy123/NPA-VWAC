<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $table = 'ward';

    protected $primaryKey = 'ward_id';

    protected $fillable = ['code', 'name', 'district_id', 'council_id'];


}
