<?php

namespace App\Http\Requests;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\ReportingPeriod;
use App\Support\AdminLocationLevel;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndicatorDataEntryRequest extends FormRequest
{
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
            'budget_allocated' => ['nullable', 'numeric'],
            'budget_used' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'remarks' => ['nullable', 'string'],

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

        if ($indicator->requires_activity && ! $this->filled('activity_name')) {
            $validator->errors()->add('activity_name', 'This indicator requires an activity name.');
        }

        if ($indicator->has_budget_implication && ! $this->filled('budget_allocated')) {
            $validator->errors()->add('budget_allocated', 'This indicator requires a budget allocated amount.');
        }

        if ($indicator->requires_evidence && ! $this->hasFile('evidence')) {
            $validator->errors()->add('evidence', 'This indicator requires supporting evidence to be attached.');
        }
    }
}
