<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** Session key holding the role ("hat") the user is currently working as. */
    public const ACTIVE_ROLE_SESSION_KEY = 'active_role';

    /** Hat used on sign-in when a user holds several roles and has not picked one. */
    private const ROLE_PRIORITY = ['Super Admin', 'Project Manager', 'Thematic Manager', 'Data Approver', 'Data Entry User'];

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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id', 'region_id');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_users')
            ->withPivot(['is_active'])
            ->withTimestamps();
    }

    public function thematicAreas()
    {
        return $this->belongsToMany(ThematicArea::class, 'thematic_area_users')
            ->withPivot(['is_active'])
            ->withTimestamps();
    }

    /**
     * Users who can be assigned to key in indicator data.
     */
    public function scopeAssignableDataEntry(Builder $query): Builder
    {
        return $query->role('Data Entry User');
    }

    public function indicatorAssignments()
    {

        return $this->hasMany(IndicatorDataAssignment::class);

    }

    public function indicatorEntries()
    {

        return $this->hasMany(IndicatorDataEntry::class, 'entered_by');

    }

    public function approvalAssignments()
    {
        return $this->hasMany(IndicatorApprovalAssignment::class);
    }

    /** @return list<int> */
    public function assignedProjectIds(): array
    {
        return $this->projects()->wherePivot('is_active', true)->pluck('projects.id')->all();
    }

    /** @return list<int> */
    public function assignedThematicAreaIds(): array
    {
        return $this->thematicAreas()->wherePivot('is_active', true)->pluck('thematic_areas.id')->all();
    }

    /**
     * Thematic areas this user may manage: those under a project they're assigned
     * to, plus any assigned to them directly as a thematic manager.
     *
     * @return list<int>
     */
    public function visibleThematicAreaIds(): array
    {
        $viaProjects = ThematicArea::query()->whereIn('project_id', $this->assignedProjectIds())->pluck('id')->all();

        return array_values(array_unique([...$viaProjects, ...$this->assignedThematicAreaIds()]));
    }

    /** @return list<int> */
    public function visibleProjectIds(): array
    {
        $viaThematicAreas = ThematicArea::query()
            ->whereIn('id', $this->assignedThematicAreaIds())
            ->pluck('project_id')->all();
        $viaIndicators = Indicator::query()
            ->whereIn('id', $this->assignedIndicatorIds())
            ->whereHas('thematicArea')
            ->with('thematicArea:id,project_id')
            ->get()->pluck('thematicArea.project_id')->filter()->all();

        return array_values(array_unique(array_map('intval', [
            ...$this->assignedProjectIds(), ...$viaThematicAreas, ...$viaIndicators,
        ])));
    }

    /**
     * Indicators this user is scoped to report on: those with an active
     * IndicatorDataAssignment naming this user directly, or naming this
     * user's organization (e.g. a bank's staff all inherit their
     * organization's assignments). Only meaningful for users who lack
     * 'indicator.view-all' — see IndicatorController/IndicatorDataEntryController.
     *
     * @return list<int>
     */
    public function assignedIndicatorIds(): array
    {
        // Coordinators collect directly within areas they manage; existing collector
        // assignments remain authoritative for ordinary Data Entry Users.
        if ($this->hasRole('Super Admin')) {
            return Indicator::query()->where('status', 'active')->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        if ($this->hasAnyRole(['Project Manager', 'Thematic Manager'])) {
            $areaIds = $this->visibleThematicAreaIds();

            return Indicator::query()->where('status', 'active')
                ->whereIn('thematic_area_id', $areaIds)->pluck('id')
                ->map(fn ($id) => (int) $id)->all();
        }

        return IndicatorDataAssignment::query()
            ->where('is_active', true)
            ->whereHas('indicator', fn ($query) => $query->where('status', 'active'))
            ->where(function ($query): void {
                $query->where('user_id', $this->id);

                if ($this->organization_id !== null) {
                    $query->orWhere('organization_id', $this->organization_id);
                }
            })
            ->distinct()
            ->pluck('indicator_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Every role the account holds (all of its hats).
     *
     * @return Collection<int, string>
     */
    public function heldRoleNames(): Collection
    {
        $this->loadMissing('roles');

        return $this->roles->pluck('name')->values();
    }

    /**
     * The one role the user is acting as right now. Permission and role checks
     * (can(), @can, hasRole(), hasAnyRole(), role middleware) are evaluated against
     * this role only, so each hat sees and does exactly what that role allows.
     */
    public function activeRoleName(): ?string
    {
        $held = $this->heldRoleNames();

        if ($held->count() <= 1) {
            return $held->first();
        }

        $chosen = session(self::ACTIVE_ROLE_SESSION_KEY);

        if (is_string($chosen) && $held->contains($chosen)) {
            return $chosen;
        }

        return collect(self::ROLE_PRIORITY)->first(fn (string $role): bool => $held->contains($role)) ?? $held->first();
    }

    /** Role check limited to the active role (Spatie's permission lookup goes through this too). */
    public function hasRole($roles, ?string $guard = null): bool
    {
        $active = $this->activeRoleName();

        return $active !== null && $this->roleNamesFrom($roles)->contains($active);
    }

    /**
     * Normalise whatever Spatie passes around (name, "a|b", id, model, enum, or lists of them) to role names.
     *
     * @return Collection<int, string>
     */
    private function roleNamesFrom(mixed $roles): Collection
    {
        if ($roles instanceof \BackedEnum) {
            $roles = $roles->value;
        }

        if ($roles instanceof Role) {
            return collect([$roles->name]);
        }

        if (is_int($roles)) {
            $this->loadMissing('roles');

            return $this->roles->where('id', $roles)->pluck('name')->values();
        }

        if (is_string($roles)) {
            return collect(str_contains($roles, '|') ? explode('|', $roles) : [$roles])->map(fn (string $name): string => trim($name));
        }

        if (is_iterable($roles)) {
            return collect($roles)->flatMap(fn (mixed $role): Collection => $this->roleNamesFrom($role))->values();
        }

        return collect();
    }
}
