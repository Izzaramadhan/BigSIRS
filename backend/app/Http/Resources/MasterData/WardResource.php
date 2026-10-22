<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $floor = $this->floor;
        $building = $floor?->building;

        $floorDisplayName = null;
        if ($floor) {
            $floorDisplayName = $building ? "{$floor->name} - {$building->name}" : $floor->name;
        }

        $wardDisplayName = $this->name;
        if ($floor) {
            if ($building) {
                $wardDisplayName = "{$this->name} ({$floor->name} - {$building->name})";
            } else {
                $wardDisplayName = "{$this->name} ({$floor->name})";
            }
        }

        return [
            'id' => $this->id,
            'floor_id' => $this->floor_id,
            'floor' => $floor ? [
                'id' => $floor->id,
                'name' => $floor->name,
                'building' => $building ? [
                    'id' => $building->id,
                    'name' => $building->name,
                ] : null,
            ] : null,
            'floor_display_name' => $floorDisplayName,
            'name' => $this->name,
            'display_name' => $wardDisplayName,
            'ward_display_name' => $wardDisplayName,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
