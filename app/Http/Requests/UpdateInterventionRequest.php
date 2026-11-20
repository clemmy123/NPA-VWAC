<?php

namespace App\Http\Requests;

use App\Models\Intervention;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'thematic_area_id' => ['sometimes', 'required', 'integer', 'exists:thematic_areas,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:30'],
            'indicator_ids' => ['nullable', 'array', 'max:'.Intervention::MAX_INDICATORS],
            'indicator_ids.*' => ['nullable', 'integer', 'exists:indicators,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'indicator_ids.max' => __('An intervention can only be linked to :max indicator.', ['max' => Intervention::MAX_INDICATORS]),
        ];
    }
}
