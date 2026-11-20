<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = null;
        if (\App\Support\LegacyAccess::hasTable('ref_pegawai') && $this->relationLoaded('employee')) {
            $name = $this->employee?->name ?? $this->employee?->person?->nama;
        }
        $name = $name
            ?? $this->name
            ?? ($this->relationLoaded('person') ? $this->person?->nama : null)
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
