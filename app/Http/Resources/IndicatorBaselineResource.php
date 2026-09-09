<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndicatorBaselineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'indicator_id' => $this->indicator_id,
            'financial_year_id' => $this->financial_year_id,
            'baseline_value' => $this->baseline_value,
            'baseline_date' => $this->baseline_date?->toDateString(),
            'source' => $this->source,
            'remarks' => $this->remarks,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
