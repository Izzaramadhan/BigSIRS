<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineResource extends JsonResource
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
            'legacy_id' => $this->legacy_id,
            'code' => $this->code,
            'name' => $this->name,
            'kfa_code' => $this->kfa_code,
            'function' => $this->function,
            'medicine_unit_id' => $this->medicine_unit_id,
            'medicine_category_id' => $this->medicine_category_id,
            'medicine_classification_id' => $this->medicine_classification_id,
            'medicine_route_id' => $this->medicine_route_id,
            'generic_medicine_id' => $this->generic_medicine_id,
            
            'unit' => $this->whenLoaded('unit'),
            'category' => $this->whenLoaded('category'),
            'classification' => $this->whenLoaded('classification'),
            'route' => $this->whenLoaded('route'),
            'generic' => $this->whenLoaded('generic'),
            
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
