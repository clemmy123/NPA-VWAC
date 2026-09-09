<?php

namespace App\Http\Requests;

use App\Models\IndicatorTarget;
use App\Models\ReportingPeriod;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreIndicatorTargetRequest extends FormRequest
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
            'dimension_option_id' => ['nullable', 'integer', 'exists:dimension_options,id'],
            'target_value' => ['required', 'numeric'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('indicator_id') || $validator->errors()->has('financial_year_id')
                || $validator->errors()->has('reporting_period_id')) {
                return;
            }

            $financialYearId = $this->input('financial_year_id');
            $reportingPeriodId = $this->input('reporting_period_id');

            if ($reportingPeriodId) {
                $reportingPeriod = ReportingPeriod::find($reportingPeriodId);

                if ($reportingPeriod && $reportingPeriod->financial_year_id !== (int) $financialYearId) {
                    $validator->errors()->add('reporting_period_id', 'The reporting period does not belong to the selected financial year.');

                    return;
                }
            }

            $duplicate = IndicatorTarget::query()
                ->where('indicator_id', $this->input('indicator_id'))
                ->where('financial_year_id', $financialYearId)
                ->when(
                    $reportingPeriodId,
                    fn ($query) => $query->where('reporting_period_id', $reportingPeriodId),
                    fn ($query) => $query->whereNull('reporting_period_id'),
                )
                ->when(
                    $this->input('dimension_option_id'),
                    fn ($query, $dimensionOptionId) => $query->where('dimension_option_id', $dimensionOptionId),
                    fn ($query) => $query->whereNull('dimension_option_id'),
                )
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('target_value', 'A target already exists for this indicator, year, period, and dimension.');
            }
        });
    }
}
