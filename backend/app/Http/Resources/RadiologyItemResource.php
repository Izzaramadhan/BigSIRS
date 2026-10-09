<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RadiologyItemResource extends JsonResource
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
            'radiology_item_group_id' => $this->radiology_item_group_id,
            'is_active' => $this->is_active,
            'group' => new RadiologyItemGroupResource($this->whenLoaded('group')),
        ];
    }
}
