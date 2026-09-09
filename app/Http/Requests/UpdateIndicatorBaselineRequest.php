<?php

namespace App\Http\Requests;

use App\Models\IndicatorBaseline;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIndicatorBaselineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'indicator_id' => ['sometimes', 'required', 'integer', 'exists:indicators,id'],
            'financial_year_id' => ['nullable', 'integer', 'exists:financial_years,id'],
            'baseline_value' => ['sometimes', 'required', 'numeric'],
            'baseline_date' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('indicator_id')) {
                return;
            }

            /** @var IndicatorBaseline $baseline */
            $baseline = $this->route('indicator_baseline');
            $indicatorId = $this->input('indicator_id', $baseline->indicator_id);
            $financialYearId = $this->has('financial_year_id') ? $this->input('financial_year_id') : $baseline->financial_year_id;

            $duplicate = IndicatorBaseline::query()
                ->where('id', '!=', $baseline->id)
                ->where('indicator_id', $indicatorId)
                ->when(
                    $financialYearId,
                    fn ($query) => $query->where('financial_year_id', $financialYearId),
                    fn ($query) => $query->whereNull('financial_year_id'),
                )
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('financial_year_id', 'A baseline already exists for this indicator and financial year.');
            }
        });
    }
}
