<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryGroupResource extends JsonResource
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
            'laboratory_category_id' => $this->laboratory_category_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'is_active' => (bool) $this->is_active,
            'harga_jaspel' => $this->harga_jaspel ?? null,
            'loinc_code' => $this->loinc_code ?? $this->category?->loinc_code ?? null,
            'loinc_url' => $this->loinc_url ?? $this->category?->loinc_url ?? null,
            'snomed_code' => $this->snomed_code ?? $this->category?->snomed_code ?? null,
            'snomed_url' => $this->snomed_url ?? $this->category?->snomed_url ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            // Relations
            'category' => new LaboratoryCategoryResource($this->whenLoaded('category')),
            'items' => LaboratoryItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),

            // For form binding
            'laboratory_item_ids' => $this->when($this->relationLoaded('items'), function () {
                return $this->items->pluck('id');
            }),
            'laboratory_item_id' => $this->when($this->relationLoaded('items'), function () {
                return $this->items->first()?->id;
            }),
        ];
    }
}
