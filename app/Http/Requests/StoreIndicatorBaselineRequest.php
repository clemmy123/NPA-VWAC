<?php

namespace App\Http\Requests;

use App\Models\IndicatorBaseline;
use App\Models\IndicatorDataEntry;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreIndicatorBaselineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'indicator_id' => ['required', 'integer', 'exists:indicators,id'],
            'financial_year_id' => ['nullable', 'integer', 'exists:financial_years,id'],
            'baseline_value' => ['required', 'numeric'],
            'baseline_date' => ['nullable', 'date'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('indicator_id')) {
                return;
            }

            $duplicate = IndicatorBaseline::query()->where('indicator_id', $this->input('indicator_id'))->exists();

            if ($duplicate) {
                $validator->errors()->add('financial_year_id', 'This indicator already has its initial baseline.');
            }

            if (IndicatorDataEntry::query()->where('indicator_id', $this->input('indicator_id'))->exists()) {
                $validator->errors()->add('indicator_id', 'A baseline cannot be added after data collection has started. Use previous collected results for comparison.');
            }
        });
    }
}
