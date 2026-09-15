<?php

namespace App\Models;

use App\Support\AdminLocationLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_type_id',
        'code',
        'name',
        'description',
        'location_level',
        'location_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function organizationType(): BelongsTo
    {
        return $this->belongsTo(OrganizationType::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function locationName(): ?string
    {
        if ($this->location_level === null || $this->location_id === null) {
            return null;
        }

        return AdminLocationLevel::name($this->location_level, (int) $this->location_id);
    }
}
