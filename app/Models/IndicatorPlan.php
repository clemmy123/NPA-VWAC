<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** Annual, institution-owned planning record. Approved records are immutable. */
class IndicatorPlan extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('supporting-documents')->useDisk('local')
            ->acceptsMimeTypes([
                'application/pdf', 'image/jpeg', 'image/png', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }

    protected $fillable = [
        'indicator_id', 'financial_year_id', 'planned_activity', 'baseline_year',
        'baseline_value', 'annual_target', 'domestic_budget', 'external_budget',
        'lead_organization_id', 'participating_organization_ids', 'status',
        'version', 'created_by', 'submitted_by', 'submitted_at',
        'confirmed_by', 'confirmed_at', 'approved_by', 'approved_at', 'review_comment',
    ];

    protected function casts(): array
    {
        return [
            'participating_organization_ids' => 'array',
            'submitted_at' => 'datetime', 'confirmed_at' => 'datetime', 'approved_at' => 'datetime',
            'domestic_budget' => 'decimal:2', 'external_budget' => 'decimal:2',
            'baseline_value' => 'decimal:4', 'annual_target' => 'decimal:4',
        ];
    }

    public function indicator(): BelongsTo { return $this->belongsTo(Indicator::class); }
    public function financialYear(): BelongsTo { return $this->belongsTo(FinancialYear::class); }
    public function leadOrganization(): BelongsTo { return $this->belongsTo(Organization::class, 'lead_organization_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function confirmer(): BelongsTo { return $this->belongsTo(User::class, 'confirmed_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    /** Immutable values captured at the moment collection starts. */
    public function snapshot(): array
    {
        return $this->only([
            'id', 'version', 'indicator_id', 'financial_year_id', 'planned_activity',
            'baseline_year', 'baseline_value', 'annual_target', 'domestic_budget',
            'external_budget', 'lead_organization_id', 'participating_organization_ids',
        ]);
    }
}
