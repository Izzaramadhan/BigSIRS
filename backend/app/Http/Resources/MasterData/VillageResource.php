<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VillageResource extends JsonResource
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
            'is_active' => (bool) $this->is_active,
            'district_id' => $this->district_id,
            'district_name' => $this->whenLoaded('district', fn () => $this->district->name),
            'regency_id' => $this->whenLoaded('district', fn () => $this->district->regency_id),
            'regency_name' => $this->whenLoaded('district', function () {
                if ($this->district->relationLoaded('regency')) {
                    return $this->district->regency->name;
                }

                return null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
