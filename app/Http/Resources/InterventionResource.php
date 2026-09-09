<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterventionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'thematic_area_id' => $this->thematic_area_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'indicators' => IndicatorResource::collection($this->whenLoaded('indicators')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
