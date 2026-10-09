<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RadiologyGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'radiology_category_id' => $this->radiology_category_id,
            'radiology_type_id' => $this->radiology_type_id,
            'activity_type_id' => $this->activity_type_id,
            'price' => (float) $this->price,
            'interpretation_price' => (float) $this->interpretation_price,
            'loinc_code' => $this->loinc_code,
            'loinc_url' => $this->loinc_url,
            'is_active' => $this->is_active,

            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ],
            'type' => [
                'id' => $this->type?->id,
                'name' => $this->type?->name,
            ],
            'activity_type' => [
                'id' => $this->activityType?->id,
                'name' => $this->activityType?->name,
            ],

            'item_groups' => RadiologyItemGroupResource::collection($this->whenLoaded('itemGroups')),
            'radiology_item_group_ids' => $this->whenLoaded('itemGroups', function () {
                return $this->itemGroups->pluck('id');
            }),

            'item_groups_count' => $this->whenCounted('itemGroups'),
        ];
    }
}
