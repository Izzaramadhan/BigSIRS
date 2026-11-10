<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = $this->employee?->name 
            ?? $this->employee?->person?->nama 
            ?? $this->person?->nama 
            ?? $this->name 
            ?? 'Unknown';

        $specializationName = $this->specialization?->nama ?? $this->specialization?->name ?? '';

        $label = $specializationName ? "{$name} — {$specializationName}" : $name;

        return [
            'id' => $this->id,
            'label' => $label,
            'name' => $name,
            'specialization_name' => $specializationName,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
