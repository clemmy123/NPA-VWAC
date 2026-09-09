<?php

namespace App\Http\Requests;

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
            'indicator_ids' => ['nullable', 'array'],
            'indicator_ids.*' => ['integer', 'exists:indicators,id'],
        ];
    }
}
