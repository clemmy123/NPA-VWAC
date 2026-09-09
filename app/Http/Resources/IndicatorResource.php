<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndicatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'thematic_area_id' => $this->thematic_area_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'measurement_type_id' => $this->measurement_type_id,
            'unit_of_measure_id' => $this->unit_of_measure_id,
            'collection_mode' => $this->collection_mode,
            'aggregation_method' => $this->aggregation_method,
            'reporting_frequency' => $this->reporting_frequency,
            'collection_scope' => $this->collection_scope,
            'requires_location' => $this->requires_location,
            'reporting_location_level' => $this->reporting_location_level,
            'requires_activity' => $this->requires_activity,
            'has_budget_implication' => $this->has_budget_implication,
            'requires_evidence' => $this->requires_evidence,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'interventions' => InterventionResource::collection($this->whenLoaded('interventions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
