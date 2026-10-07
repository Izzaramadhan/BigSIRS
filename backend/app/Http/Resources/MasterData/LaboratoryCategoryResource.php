<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryCategoryResource extends JsonResource
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
            'description' => $this->description,
            'type' => $this->type,
            'loinc_code' => $this->loinc_code,
            'loinc_url' => $this->loinc_url,
            'snomed_code' => $this->snomed_code,
            'snomed_url' => $this->snomed_url,
            'is_active' => (bool) $this->is_active,
            'legacy_id' => $this->legacy_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
