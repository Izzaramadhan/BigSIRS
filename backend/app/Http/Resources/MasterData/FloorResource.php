<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FloorResource extends JsonResource
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
            'building_id' => $this->building_id,
            'building' => $this->whenLoaded('building', function () {
                return $this->building ? [
                    'id' => $this->building->id,
                    'name' => $this->building->name,
                ] : null;
            }, function () {
                return $this->building ? [
                    'id' => $this->building->id,
                    'name' => $this->building->name,
                ] : null;
            }),
            'name' => $this->name,
            'display_name' => $this->building ? "{$this->name} - {$this->building->name}" : $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
