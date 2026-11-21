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

        'name', 'name_en', 'name_sw', 'official_code',

        'description',

        'status',

        'created_by',

    ];

    protected static function booted(): void
    {
        // `name` is the English name; keep `name_en` mirroring it unless set explicitly.
        static::saving(function (self $area): void {
            if ($area->isDirty('name') && ! $area->isDirty('name_en')) {
                $area->name_en = $area->name;
            }
        });
    }

    /** Official bilingual presentation; keep name as a stable legacy column. */
    public function getLocalizedNameAttribute(): string
    {
        return (string) ((app()->getLocale() === 'sw' ? $this->name_sw : $this->name_en)
            ?: $this->name);
    }

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
