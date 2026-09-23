<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TariffTypeComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'legacy_id' => $this->legacy_id,
            'tariff_component_id' => $this->tariff_component_id,
            'percentage' => $this->percentage,
            'needs_review' => $this->needs_review,
            'component' => [
                'id' => $this->component->id ?? null,
                'name' => $this->component->name ?? null,
                'is_active' => $this->component->is_active ?? null,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
