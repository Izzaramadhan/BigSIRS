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
            'description' => $this->description,
            'is_active' => (bool)$this->is_active,
            'needs_review' => (bool)$this->needs_review,
        ];
    }
}
