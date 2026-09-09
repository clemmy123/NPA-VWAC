<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThematicArea extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [

        'project_id',

        'name',

        'description',

        'status',

        'created_by',

    ];

    public function project()
    {

        return $this->belongsTo(Project::class);

    }

    public function indicators()
    {

        return $this->hasMany(Indicator::class);

    }

    public function interventions()
    {

        return $this->hasMany(Intervention::class);

    }

    public function users()
    {

        return $this->belongsToMany(User::class, 'thematic_area_users')->withPivot(['is_active'])->withTimestamps();

    }
}
