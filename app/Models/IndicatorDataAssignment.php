<?php

namespace App\Models;

use App\Support\AdminLocationLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorDataAssignment extends Model
{
    use HasFactory;

    protected $fillable = [

        'indicator_id',

        'user_id',

        'location_level',

        'location_id',

        'organization_id',

        'data_source_id',

        'is_active',

    ];

    protected function casts(): array
    {
        return [

            'is_active' => 'boolean',

        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function locationName(): ?string
    {
        if ($this->location_level === null || $this->location_id === null) {
            return null;
        }

        return AdminLocationLevel::name($this->location_level, (int) $this->location_id);
    }
}
