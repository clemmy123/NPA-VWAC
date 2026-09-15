<?php

namespace App\Http\Requests;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\ReportingPeriod;
use App\Support\AdminLocationLevel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndicatorDataEntryRequest extends FormRequest
{
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
            'budget_allocated' => ['nullable', 'numeric'],
            'budget_used' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'remarks' => ['nullable', 'string'],

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
        $activityName = $this->has('activity_name') ? $this->input('activity_name') : $entry->activity_name;
        $budgetAllocated = $this->has('budget_allocated') ? $this->input('budget_allocated') : $entry->budget_allocated;

        if ($indicator->requires_location && ! filled($locationLevel)) {
            $validator->errors()->add('location_level', 'This indicator requires a reporting location.');
        }

        if ($indicator->requires_activity && ! filled($activityName)) {
            $validator->errors()->add('activity_name', 'This indicator requires an activity name.');
        }

        if ($indicator->has_budget_implication && ! filled($budgetAllocated)) {
            $validator->errors()->add('budget_allocated', 'This indicator requires a budget allocated amount.');
        }

        if ($indicator->requires_evidence && ! $this->hasFile('evidence') && $entry->getMedia('evidence')->isEmpty()) {
            $validator->errors()->add('evidence', 'This indicator requires supporting evidence to be attached.');
        }
    }
}
