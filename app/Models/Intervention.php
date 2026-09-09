<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Intervention extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'thematic_area_id',
        'name',
        'description',
        'status',
        'created_by',
    ];

    public function thematicArea()
    {
        return $this->belongsTo(ThematicArea::class);
    }

    public function indicators()
    {
        return $this->belongsToMany(Indicator::class, 'indicator_interventions')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
