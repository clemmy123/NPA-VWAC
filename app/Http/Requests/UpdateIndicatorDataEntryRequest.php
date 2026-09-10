<?php

namespace App\Http\Requests;

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
            'data_source_id' => ['nullable', 'integer', 'exists:data_sources,id'],
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
        });
    }
}
