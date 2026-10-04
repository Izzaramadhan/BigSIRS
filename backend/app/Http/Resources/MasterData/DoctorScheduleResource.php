<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorScheduleResource extends JsonResource
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
            'doctor_id' => $this->doctor_id,
            'doctor' => [
                'id' => $this->whenLoaded('doctor', function () { return $this->doctor->id; }),
                'name' => $this->whenLoaded('doctor', function () { return $this->doctor->employee->name ?? ''; }),
                'specialization' => $this->whenLoaded('doctor', function () { return $this->doctor->specialization->name ?? ''; }),
            ],
            'polyclinic_id' => $this->polyclinic_id,
            'polyclinic' => [
                'id' => $this->whenLoaded('polyclinic', function () { return $this->polyclinic->id; }),
                'name' => $this->whenLoaded('polyclinic', function () { return $this->polyclinic->name; }),
            ],
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'is_holiday' => (bool)$this->is_holiday,
            'online_quota' => $this->online_quota,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
