<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndicatorDataEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'indicator_id' => $this->indicator_id,
            'financial_year_id' => $this->financial_year_id,
            'reporting_period_id' => $this->reporting_period_id,
            'entry_date' => $this->entry_date?->toDateString(),
            'activity_name' => $this->activity_name,
            'activity_description' => $this->activity_description,
            'location_level' => $this->location_level,
            'location_id' => $this->location_id,
            'organization_id' => $this->organization_id,
            'actual_value' => $this->actual_value,
            'budget_allocated' => $this->budget_allocated,
            'budget_used' => $this->budget_used,
            'currency' => $this->currency,
            'source_type' => $this->source_type,
            'remarks' => $this->remarks,
            'entered_by' => $this->entered_by,
            'submitted_at' => $this->submitted_at,
            'approved_at' => $this->approved_at,
            'approved_by' => $this->approved_by,
            'status' => $this->status,
            'rows' => $this->whenLoaded('rows', fn () => $this->rows->map(fn ($row) => [
                'id' => $row->id,
                'label' => $row->label,
                'value' => $row->value,
                'dimension_option_ids' => $row->relationLoaded('dimensionOptions')
                    ? $row->dimensionOptions->pluck('id')->all()
                    : [],
            ])),
            'expenses' => $this->whenLoaded('expenses', fn () => $this->expenses->map(fn ($expense) => [
                'id' => $expense->id,
                'expense_category' => $expense->expense_category,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'currency' => $expense->currency,
            ])),
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews->map(fn ($review) => [
                'id' => $review->id,
                'reviewed_by' => $review->reviewed_by,
                'action' => $review->action,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
            ])),
            'evidence' => $this->whenLoaded('media', fn () => $this->getMedia('evidence')->map(fn ($media) => [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'download_url' => route('indicator-data-entries.evidence.download', [$this->resource, $media]),
                'created_at' => $media->created_at,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
