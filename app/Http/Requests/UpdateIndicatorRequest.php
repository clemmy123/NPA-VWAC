<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndicatorRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('project_id')) {
            $areaId = $this->input('thematic_area_id', $this->route('indicator')?->thematic_area_id);
            $this->merge(['project_id' => \App\Models\ThematicArea::find($areaId)?->project_id]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $indicator = $this->route('indicator');
        $thematicAreaId = $this->input('thematic_area_id', $indicator?->thematic_area_id);

        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'thematic_area_id' => [
                'sometimes', 'required', 'integer',
                Rule::exists('thematic_areas', 'id')->where('project_id', $this->input('project_id', $indicator?->thematicArea?->project_id)),
            ],
            'code' => [
                'sometimes', 'required', 'string', 'max:80',
                Rule::unique('indicators', 'code')->where('thematic_area_id', $thematicAreaId)->ignore($indicator),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'measurement_type_id' => ['nullable', 'integer', 'exists:measurement_types,id'],
            'unit_of_measure_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'collection_mode' => ['nullable', Rule::in(['progressive', 'periodic', 'snapshot'])],
            'aggregation_method' => ['nullable', Rule::in(['sum', 'average', 'latest', 'count', 'max', 'min'])],
            'reporting_frequency' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'biannual', 'annual'])],
            'collection_scope' => ['nullable', Rule::in(['national', 'geographic', 'institutional', 'mixed'])],
            'requires_location' => ['nullable', 'boolean'],
            'reporting_location_level' => ['nullable', Rule::in(['region', 'district', 'council', 'division', 'township', 'ward', 'village_mtaa', 'kitongoji'])],
            'requires_activity' => ['nullable', 'boolean'],
            'has_budget_implication' => ['nullable', 'boolean'],
            'requires_evidence' => ['nullable', 'boolean'],
            'requires_hierarchical_approval' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:30'],
        ];
    }
}
