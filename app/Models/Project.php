<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [

        'code',

        'name',

        'description',

        'start_date',

        'end_date',

        'status',

        'created_by',

    ];

    protected function casts(): array
    {
        return [

            'start_date' => 'date',

            'end_date' => 'date',

        ];
    }

    public function thematicAreas()
    {

        return $this->hasMany(ThematicArea::class);

    }

    public function users()
    {

        return $this->belongsToMany(User::class, 'project_users')->withPivot(['is_active'])->withTimestamps();

    }

    public function creator()
    {

        return $this->belongsTo(User::class, 'created_by');

    }
}
