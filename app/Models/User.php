<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'middle_name',
        'last_name',
        'phone_number',
        'gender',
        'status',
        'organization_id',
        'region_id',
        'auth_provider',
        'password_login_enabled',
        'external_verified_at',
        'global_user_id',
        'jumuishi_sync_status',
        'jumuishi_synced_at',
        'jumuishi_sync_error',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'jumuishi_sync_error',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_login_enabled' => 'boolean',
            'external_verified_at' => 'datetime',
            'jumuishi_synced_at' => 'datetime',
            'global_user_id' => 'integer',
        ];
    }

    protected $attributes = [
        'status' => 'active',
        'auth_provider' => 'jumuishi',
        'password_login_enabled' => false,
        'jumuishi_sync_status' => 'pending',
    ];

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->isDirty(['first_name', 'middle_name', 'last_name'])) {
                $user->attributes['name'] = $user->name;
            }

            if ($user->exists && $user->isDirty(['first_name', 'middle_name', 'last_name', 'gender', 'email', 'status'])
                && ! $user->isDirty(['jumuishi_sync_status', 'jumuishi_synced_at'])) {
                $user->jumuishi_sync_status = 'pending';
            }
        });
    }

    public function setNameAttribute(string $value): void
    {
        $parts = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        $this->attributes['name'] = trim($value);
        $this->attributes['first_name'] = $parts[0] ?? '';
        $this->attributes['middle_name'] = count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : null;
        $this->attributes['last_name'] = count($parts) > 1 ? end($parts) : '';
    }

    public function getNameAttribute(): string
    {
        $name = trim(implode(' ', array_filter([
            $this->first_name, $this->middle_name, $this->last_name,
        ], fn ($part) => $part !== null && $part !== '')));

        return $name !== '' ? $name : ($this->attributes['name'] ?? '');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function region(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id', 'region_id');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_users')
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    public function thematicAreas()
    {
        return $this->belongsToMany(ThematicArea::class, 'thematic_area_users')
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    public function indicatorAssignments()


    {


        return $this->hasMany(IndicatorDataAssignment::class);


    }

    public function indicatorEntries()


    {


        return $this->hasMany(IndicatorDataEntry::class, 'entered_by');


    }
}
