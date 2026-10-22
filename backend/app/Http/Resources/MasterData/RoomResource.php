<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ward = $this->ward;
        $floor = $ward?->floor;
        $building = $floor?->building;
        $roomClass = $this->roomClass;

        $wardDisplayName = null;
        if ($ward) {
            if ($floor) {
                if ($building) {
                    $wardDisplayName = "{$ward->name} ({$floor->name} - {$building->name})";
                } else {
                    $wardDisplayName = "{$ward->name} ({$floor->name})";
                }
            } else {
                $wardDisplayName = $ward->name;
            }
        }

        return [
            'id' => $this->id,
            'ward_id' => $this->ward_id,
            'ward' => $ward ? [
                'id' => $ward->id,
                'name' => $ward->name,
                'floor' => $floor ? [
                    'id' => $floor->id,
                    'name' => $floor->name,
                    'building' => $building ? [
                        'id' => $building->id,
                        'name' => $building->name,
                    ] : null,
                ] : null,
            ] : null,
            'ward_display_name' => $wardDisplayName,
            'code' => $this->code,
            'name' => $this->name,
            'room_class_id' => $this->room_class_id ?? $this->class_id,
            'room_class' => $roomClass ? [
                'id' => $roomClass->id,
                'code' => $roomClass->code,
                'name' => $roomClass->name,
            ] : null,
            'class_id' => $this->room_class_id ?? $this->class_id,
            'class' => $roomClass ? [
                'id' => $roomClass->id,
                'code' => $roomClass->code,
                'name' => $roomClass->name,
            ] : null,
            'display_name' => "{$this->name} - " . ($ward ? $ward->name : 'Tanpa Bangsal') . ' - ' . ($roomClass ? $roomClass->name : 'Tanpa Kelas'),
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
