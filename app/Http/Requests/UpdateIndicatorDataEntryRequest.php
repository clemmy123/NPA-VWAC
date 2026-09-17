<?php

namespace App\Http\Requests;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\ReportingPeriod;
use App\Support\AdminLocationLevel;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndicatorDataEntryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        /** @var IndicatorDataEntry|null $entry */
        $entry = $this->route('indicator_data_entry');
        $indicatorId = $this->input('indicator_id', $entry?->indicator_id);
        $indicator = $indicatorId ? Indicator::find($indicatorId) : null;

        if ($this->filled('location_id') && $indicator?->requires_location && $indicator->reporting_location_level) {
            $this->merge(['location_level' => $indicator->reporting_location_level]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'indicator_id' => ['sometimes', 'required', 'integer', 'exists:indicators,id'],
            'financial_year_id' => ['sometimes', 'required', 'integer', 'exists:financial_years,id'],
            'reporting_period_id' => ['nullable', 'integer', 'exists:reporting_periods,id'],
            'entry_date' => ['sometimes', 'required', 'date'],
            'activity_name' => ['nullable', 'string', 'max:255'],
            'activity_description' => ['nullable', 'string'],
            'location_level' => ['nullable', 'string', Rule::in(AdminLocationLevel::levels()), 'required_with:location_id'],
            'location_id' => ['nullable', 'integer', 'required_with:location_level'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'actual_value' => ['nullable', 'numeric'],
            'actual_text' => ['nullable', 'string'],
            'budget_allocated' => ['nullable', 'numeric'],
            'budget_used' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'remarks' => ['nullable', 'string'],

            'activities' => ['sometimes', 'array'],
            'activities.*.name' => ['required', 'string', 'max:255'],
            'activities.*.description' => ['nullable', 'string'],
            'activities.*.participants_total' => ['required', 'integer', 'min:0'],
            'activities.*.women' => ['nullable', 'integer', 'min:0'],
            'activities.*.men' => ['nullable', 'integer', 'min:0'],
            'activities.*.children' => ['nullable', 'integer', 'min:0'],
            'activities.*.other' => ['nullable', 'integer', 'min:0'],

            'rows' => ['sometimes', 'array'],
            'rows.*.label' => ['nullable', 'string', 'max:255'],
            'rows.*.value' => ['required', 'numeric'],
            'rows.*.dimension_option_ids' => ['nullable', 'array'],
            'rows.*.dimension_option_ids.*' => ['integer', 'exists:dimension_options,id'],

            'expenses' => ['sometimes', 'array'],
            'expenses.*.expense_category' => ['nullable', 'string', 'max:255'],
            'expenses.*.description' => ['required', 'string'],
            'expenses.*.amount' => ['required', 'numeric'],
            'expenses.*.currency' => ['nullable', 'string', 'size:3'],

            'evidence' => ['nullable', 'array'],
            'evidence.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $level = $this->input('location_level');
            $id = $this->input('location_id');

            if ($level && $id && ! $validator->errors()->has('location_level') && ! AdminLocationLevel::exists($level, (int) $id)) {
                $validator->errors()->add('location_id', 'The selected location does not exist at the given level.');
            }

            if (! $validator->errors()->has('indicator_id')) {
                $this->applyIndicatorConfigRules($validator);
            }

            $this->applyPeriodWindowRules($validator);
        });
    }

    private function applyPeriodWindowRules(Validator $validator): void
    {
        if ($this->user()?->can('indicator-data.override-period')) {
            return;
        }

        /** @var IndicatorDataEntry $entry */
        $entry = $this->route('indicator_data_entry');
        $today = now()->startOfDay();
        $entryDateValue = $this->has('entry_date') ? $this->input('entry_date') : $entry->entry_date;

        if ($entryDateValue && Carbon::parse($entryDateValue)->startOfDay()->gt($today)) {
            $validator->errors()->add('entry_date', 'You cannot enter data for a future date.');
        }

        $financialYearId = $this->has('financial_year_id') ? $this->input('financial_year_id') : $entry->financial_year_id;

        if (! $validator->errors()->has('financial_year_id') && $financialYearId) {
            $financialYear = FinancialYear::find($financialYearId);

            if ($financialYear && $financialYear->start_date->gt($today)) {
                $validator->errors()->add('financial_year_id', 'You cannot enter data for a future financial year.');
            }
        }

        $reportingPeriodId = $this->has('reporting_period_id') ? $this->input('reporting_period_id') : $entry->reporting_period_id;

        if (! $validator->errors()->has('reporting_period_id') && $reportingPeriodId) {
            $period = ReportingPeriod::find($reportingPeriodId);

            if ($period && $period->start_date->gt($today)) {
                $validator->errors()->add('reporting_period_id', 'You cannot enter data for a future reporting period.');
            }
            if ($period && (int) $period->financial_year_id !== (int) $financialYearId) {
                $validator->errors()->add('reporting_period_id', 'The reporting period must belong to the selected financial year.');
            }
        }
    }

    private function applyIndicatorConfigRules(Validator $validator): void
    {
        /** @var IndicatorDataEntry $entry */
        $entry = $this->route('indicator_data_entry');

        $indicatorId = $this->has('indicator_id') ? $this->input('indicator_id') : $entry->indicator_id;
        $indicator = Indicator::find($indicatorId);

        if (! $indicator) {
            return;
        }

        $locationLevel = $this->has('location_level') ? $this->input('location_level') : $entry->location_level;
        if ($indicator->requires_location && ! filled($locationLevel)) {
            $validator->errors()->add('location_level', 'This indicator requires a reporting location.');
        }

        if ($indicator->requires_location && $indicator->reporting_location_level
            && filled($locationLevel)
            && $locationLevel !== $indicator->reporting_location_level) {
            $validator->errors()->add('location_level', 'This indicator must stop at the '.str_replace('_', ' ', $indicator->reporting_location_level).' level.');
        }

        $activityName = $this->has('activity_name') ? $this->input('activity_name') : $entry->activity_name;
        $activitiesPresent = $this->has('activities') ? count($this->input('activities', [])) > 0 : $entry->activities()->exists();
        if ($indicator->requires_activity && ! $activitiesPresent && ! filled($activityName)) {
            $validator->errors()->add('activities', 'Add at least one activity for this indicator.');
            $validator->errors()->add('activity_name', 'This indicator requires at least one activity.');
        }

        $periodTypes = ['weekly' => 'week', 'monthly' => 'month', 'quarterly' => 'quarter', 'biannual' => 'semi_annual', 'annual' => 'annual'];
        $requiredPeriodType = $periodTypes[$indicator->reporting_frequency] ?? null;
        $periodId = $this->has('reporting_period_id') ? $this->input('reporting_period_id') : $entry->reporting_period_id;
        $period = $periodId ? ReportingPeriod::find($periodId) : null;
        $financialYearId = $this->has('financial_year_id') ? $this->input('financial_year_id') : $entry->financial_year_id;
        $periodIsConfigured = $requiredPeriodType !== null && ReportingPeriod::query()
            ->where('financial_year_id', $financialYearId)
            ->where('period_type', $requiredPeriodType)->where('is_active', true)->exists();
        if ($periodIsConfigured && ! $period) {
            $validator->errors()->add('reporting_period_id', 'Select the '.$indicator->reporting_frequency.' reporting period.');
        }
        if ($period && $requiredPeriodType !== $period->period_type) {
            $validator->errors()->add('reporting_period_id', 'The reporting period does not match this indicator frequency.');
        }
        $entryDate = $this->has('entry_date') ? $this->input('entry_date') : $entry->entry_date;
        if (! $this->user()?->can('indicator-data.override-period') && $period && $entryDate && ! Carbon::parse($entryDate)->betweenIncluded($period->start_date, $period->end_date)) {
            $validator->errors()->add('entry_date', 'The entry date must fall inside the selected reporting period.');
        }

        $actualValue = $this->has('actual_value') ? $this->input('actual_value') : $entry->actual_value;
        $actualText = $this->has('actual_text') ? $this->input('actual_text') : $entry->actual_text;
        if (in_array($indicator->measurementType?->code, ['text', 'qualitative'], true) && filled($actualValue)) {
            $validator->errors()->add('actual_value', 'This indicator accepts a text response, not a numeric value.');
        }
        if (! in_array($indicator->measurementType?->code, ['text', 'qualitative'], true) && filled($actualText)) {
            $validator->errors()->add('actual_text', 'This indicator requires a numeric or Yes/No response.');
        }
        if (filled($actualValue)) {
            $actual = (float) $actualValue;
            $type = $indicator->measurementType?->code;
            if ($type === 'count' && floor($actual) !== $actual) {
                $validator->errors()->add('actual_value', 'A count must be a whole number.');
            }
            if ($type === 'percentage' && ($actual < 0 || $actual > 100)) {
                $validator->errors()->add('actual_value', 'A percentage must be between 0 and 100.');
            }
            if ($type === 'yes_no' && ! in_array($actual, [0.0, 1.0], true)) {
                $validator->errors()->add('actual_value', 'Select either Yes or No.');
            }
            if (in_array($type, ['count', 'currency'], true) && $actual < 0) {
                $validator->errors()->add('actual_value', 'The actual value cannot be negative.');
            }
        }
    }
}
