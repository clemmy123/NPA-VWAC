<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectUser extends Model
{
    use HasFactory;

    protected $fillable = [


        'project_id',


        'user_id',


        'role',


        'is_active',


    ];

    protected function casts(): array
    {
        return [

            'is_active' => 'boolean',

        ];
    }

    public function project()


    {


        return $this->belongsTo(Project::class);


    }
    public function user()

    {

        return $this->belongsTo(User::class);

    }
}
