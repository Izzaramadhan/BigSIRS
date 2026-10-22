<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $room = $this->room;
        $ward = $room?->ward;
        $floor = $ward?->floor;
        $building = $floor?->building;
        $roomClass = $room?->roomClass;

        $roomTableDisplayName = $room
            ? ($ward ? "{$room->name} - {$ward->name}" : $room->name)
            : null;

        $roomFormDisplayName = $room
            ? "{$room->name} - " . ($ward ? $ward->name : 'Tanpa Bangsal') . ' - ' . ($roomClass ? $roomClass->name : 'Tanpa Kelas')
            : null;

        return [
            'id' => $this->id,
            'legacy_id' => $this->legacy_id,
            'room_id' => $this->room_id,
            'name' => $this->name,
            'description' => $this->description,
            'room' => $room ? [
                'id' => $room->id,
                'code' => $room->code,
                'name' => $room->name,
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
                'room_class' => $roomClass ? [
                    'id' => $roomClass->id,
                    'code' => $roomClass->code,
                    'name' => $roomClass->name,
                ] : null,
                'class' => $roomClass ? [
                    'id' => $roomClass->id,
                    'code' => $roomClass->code,
                    'name' => $roomClass->name,
                ] : null,
            ] : null,
            'room_table_display_name' => $roomTableDisplayName,
            'room_form_display_name' => $roomFormDisplayName,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
