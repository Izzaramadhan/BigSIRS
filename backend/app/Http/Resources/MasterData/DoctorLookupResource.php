<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->employee ? $this->employee->name : 'Unknown',
            'specialization' => $this->specialization ? $this->specialization->name : null,
            'is_active' => $this->is_active,
        ];
    }
}
