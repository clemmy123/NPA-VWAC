<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class IndicatorDataEntry extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    private const EVIDENCE_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('evidence')
            ->useDisk('local')
            ->acceptsMimeTypes(self::EVIDENCE_MIME_TYPES);
    }

    protected $fillable = [

        'reference_no',

        'indicator_id',

        'financial_year_id',

        'reporting_period_id',

        'entry_date',

        'activity_name',

        'activity_description',

        'location_level',

        'location_id',

        'organization_id',

        'actual_value',

        'actual_text',

        'budget_allocated',

        'budget_used',

        'currency',

        'source_type',

        'remarks',

        'entered_by',

        'submitted_at',

        'approved_at',

        'approved_by',

        'status',

    ];

    protected function casts(): array
    {
        return [

            'entry_date' => 'date',

            'actual_value' => 'decimal:4',

            'budget_allocated' => 'decimal:2',

            'budget_used' => 'decimal:2',

            'submitted_at' => 'datetime',

            'approved_at' => 'datetime',

        ];
    }

    public function indicator()
    {

        return $this->belongsTo(Indicator::class);

    }

    public function financialYear()
    {

        return $this->belongsTo(FinancialYear::class);

    }

    public function reportingPeriod()
    {

        return $this->belongsTo(ReportingPeriod::class);

    }

    public function rows()
    {

        return $this->hasMany(IndicatorDataEntryRow::class);

    }

    public function expenses()
    {

        return $this->hasMany(IndicatorDataEntryExpense::class);

    }

    public function activities()
    {
        return $this->hasMany(IndicatorDataEntryActivity::class);
    }

    public function reviews()
    {

        return $this->hasMany(IndicatorDataReview::class);

    }

    public function approvalSteps()
    {
        return $this->hasMany(IndicatorDataEntryApproval::class)->orderBy('sequence');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
