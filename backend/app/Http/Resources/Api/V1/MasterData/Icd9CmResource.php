<?php

namespace App\Http\Resources\Api\V1\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Icd9CmResource extends JsonResource
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
            'english_name' => $this->english_name,
            'description' => $this->description,
            'is_active' => (bool)$this->is_active,
            'needs_review' => (bool)$this->needs_review,
            'inacbg_code' => $this->inacbg_code,
            'inacbg_name' => $this->inacbg_name,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
