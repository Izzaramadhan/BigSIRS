<?php

namespace App\Http\Resources\MasterData;

use App\Models\Legacy\LegacyProvince;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isLegacy = $this->resource instanceof LegacyProvince;

        return [
            'id' => $this->id,
            'legacy_id' => $this->legacy_id,
            'code' => $isLegacy ? $this->code : null,
            'name' => $this->name,
            'is_active' => $isLegacy ? (bool) $this->is_active : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

