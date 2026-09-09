<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndicatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $indicator = $this->route('indicator');
        $thematicAreaId = $this->input('thematic_area_id', $indicator?->thematic_area_id);

        return [
            'thematic_area_id' => ['sometimes', 'required', 'integer', 'exists:thematic_areas,id'],
            'code' => [
                'sometimes', 'required', 'string', 'max:80',
                Rule::unique('indicators', 'code')->where('thematic_area_id', $thematicAreaId)->ignore($indicator),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'measurement_type_id' => ['nullable', 'integer', 'exists:measurement_types,id'],
            'unit_of_measure_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'collection_mode' => ['nullable', 'string', 'max:30'],
            'aggregation_method' => ['nullable', 'string', 'max:30'],
            'reporting_frequency' => ['nullable', 'string', 'max:30'],
            'collection_scope' => ['nullable', 'string', 'max:30'],
            'requires_location' => ['nullable', 'boolean'],
            'reporting_location_level' => ['nullable', 'string', 'max:30'],
            'requires_activity' => ['nullable', 'boolean'],
            'has_budget_implication' => ['nullable', 'boolean'],
            'requires_evidence' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:30'],
        ];
    }
}
