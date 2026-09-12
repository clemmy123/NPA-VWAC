<?php

namespace App\Http\Requests;

use App\Models\IndicatorBaseline;
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

            $duplicate = IndicatorBaseline::query()
                ->where('indicator_id', $this->input('indicator_id'))
                ->when(
                    $this->input('financial_year_id'),
                    fn ($query, $financialYearId) => $query->where('financial_year_id', $financialYearId),
                    fn ($query) => $query->whereNull('financial_year_id'),
                )
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('financial_year_id', 'A baseline already exists for this indicator and financial year.');
            }
        });
    }
}
