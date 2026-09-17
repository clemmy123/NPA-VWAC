<?php

namespace App\Http\Requests;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\ReportingPeriod;
use App\Support\AdminLocationLevel;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndicatorDataEntryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $today = now()->startOfDay();
        $financialYear = FinancialYear::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();
        $indicator = $this->filled('indicator_id') ? Indicator::find($this->input('indicator_id')) : null;
        $periodType = $indicator ? (['weekly' => 'week', 'monthly' => 'month', 'quarterly' => 'quarter', 'biannual' => 'semi_annual', 'annual' => 'annual'][$indicator->reporting_frequency] ?? null) : null;
        $period = ($financialYear && $periodType) ? ReportingPeriod::query()
            ->where('financial_year_id', $financialYear->id)->where('period_type', $periodType)
            ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first() : null;

        $automatic = [
            'entry_date' => $this->user()?->can('indicator-data.override-period') && $this->filled('entry_date') ? $this->input('entry_date') : $today->toDateString(),
            'organization_id' => $this->user()?->organization_id ?? $this->input('organization_id'),
        ];
        if (! $this->filled('financial_year_id') && $financialYear) {
            $automatic['financial_year_id'] = $financialYear->id;
        }
        if (! $this->filled('reporting_period_id') && $period) {
            $automatic['reporting_period_id'] = $period->id;
        }
        if ($this->filled('location_id') && $indicator?->requires_location && $indicator->reporting_location_level) {
            $automatic['location_level'] = $indicator->reporting_location_level;
        }
        $this->merge($automatic);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'indicator_id' => ['required', 'integer', 'exists:indicators,id'],
            'financial_year_id' => ['required', 'integer', 'exists:financial_years,id'],
            'reporting_period_id' => ['nullable', 'integer', 'exists:reporting_periods,id'],
            'entry_date' => ['required', 'date'],
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

            'activities' => ['nullable', 'array'],
            'activities.*.name' => ['required', 'string', 'max:255'],
            'activities.*.description' => ['nullable', 'string'],
            'activities.*.participants_total' => ['required', 'integer', 'min:0'],
            'activities.*.women' => ['nullable', 'integer', 'min:0'],
            'activities.*.men' => ['nullable', 'integer', 'min:0'],
            'activities.*.children' => ['nullable', 'integer', 'min:0'],
            'activities.*.other' => ['nullable', 'integer', 'min:0'],

            'rows' => ['nullable', 'array'],
            'rows.*.label' => ['nullable', 'string', 'max:255'],
            'rows.*.value' => ['required', 'numeric'],
            'rows.*.dimension_option_ids' => ['nullable', 'array'],
            'rows.*.dimension_option_ids.*' => ['integer', 'exists:dimension_options,id'],

            'expenses' => ['nullable', 'array'],
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
                $this->applyIndicatorConfigRules($validator, Indicator::find($this->input('indicator_id')));
            }

            $this->applyPeriodWindowRules($validator);
        });
    }

    private function applyPeriodWindowRules(Validator $validator): void
    {
        if ($this->user()?->can('indicator-data.override-period')) {
            return;
        }

        $today = now()->startOfDay();

        if (! $validator->errors()->has('entry_date') && $this->filled('entry_date')
            && Carbon::parse($this->input('entry_date'))->startOfDay()->gt($today)) {
            $validator->errors()->add('entry_date', 'You cannot enter data for a future date.');
        }

        if (! $validator->errors()->has('financial_year_id') && $this->filled('financial_year_id')) {
            $financialYear = FinancialYear::find($this->input('financial_year_id'));

            if ($financialYear && $financialYear->start_date->gt($today)) {
                $validator->errors()->add('financial_year_id', 'You cannot enter data for a future financial year.');
            }
        }

        if (! $validator->errors()->has('reporting_period_id') && $this->filled('reporting_period_id')) {
            $period = ReportingPeriod::find($this->input('reporting_period_id'));

            if ($period && $period->start_date->gt($today)) {
                $validator->errors()->add('reporting_period_id', 'You cannot enter data for a future reporting period.');
            }
            if ($period && (int) $period->financial_year_id !== (int) $this->input('financial_year_id')) {
                $validator->errors()->add('reporting_period_id', 'The reporting period must belong to the selected financial year.');
            }
        }
    }

    private function applyIndicatorConfigRules(Validator $validator, ?Indicator $indicator): void
    {
        if (! $indicator) {
            return;
        }

        if ($indicator->requires_location && ! $this->filled('location_level')) {
            $validator->errors()->add('location_level', 'This indicator requires a reporting location.');
        }

        if ($indicator->requires_location && $indicator->reporting_location_level
            && $this->filled('location_level')
            && $this->input('location_level') !== $indicator->reporting_location_level) {
            $validator->errors()->add('location_level', 'This indicator must stop at the '.str_replace('_', ' ', $indicator->reporting_location_level).' level.');
        }

        if ($indicator->requires_activity && empty($this->input('activities', [])) && ! $this->filled('activity_name')) {
            $validator->errors()->add('activities', 'Add at least one activity for this indicator.');
            $validator->errors()->add('activity_name', 'This indicator requires at least one activity.');
        }

        $periodTypes = ['weekly' => 'week', 'monthly' => 'month', 'quarterly' => 'quarter', 'biannual' => 'semi_annual', 'annual' => 'annual'];
        $requiredPeriodType = $periodTypes[$indicator->reporting_frequency] ?? null;
        $period = $this->filled('reporting_period_id') ? ReportingPeriod::find($this->input('reporting_period_id')) : null;
        $periodIsConfigured = $requiredPeriodType !== null && ReportingPeriod::query()
            ->where('financial_year_id', $this->input('financial_year_id'))
            ->where('period_type', $requiredPeriodType)->where('is_active', true)->exists();
        if ($periodIsConfigured && ! $period) {
            $validator->errors()->add('reporting_period_id', 'Select the '.$indicator->reporting_frequency.' reporting period.');
        }
        if ($period && $requiredPeriodType !== $period->period_type) {
            $validator->errors()->add('reporting_period_id', 'The reporting period does not match this indicator frequency.');
        }
        if (! $this->user()?->can('indicator-data.override-period') && $period && $this->filled('entry_date') && ! Carbon::parse($this->input('entry_date'))->betweenIncluded($period->start_date, $period->end_date)) {
            $validator->errors()->add('entry_date', 'The entry date must fall inside the selected reporting period.');
        }

        if (in_array($indicator->measurementType?->code, ['text', 'qualitative'], true) && $this->filled('actual_value')) {
            $validator->errors()->add('actual_value', 'This indicator accepts a text response, not a numeric value.');
        }

        if (! in_array($indicator->measurementType?->code, ['text', 'qualitative'], true) && $this->filled('actual_text')) {
            $validator->errors()->add('actual_text', 'This indicator requires a numeric or Yes/No response.');
        }

        if ($this->filled('actual_value')) {
            $actual = (float) $this->input('actual_value');
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
