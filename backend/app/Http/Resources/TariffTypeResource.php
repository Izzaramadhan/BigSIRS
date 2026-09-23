<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TariffTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'legacy_id' => $this->legacy_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'needs_review' => $this->needs_review,
            'components' => TariffTypeComponentResource::collection($this->whenLoaded('components')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
