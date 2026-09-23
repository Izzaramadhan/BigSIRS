<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PolyclinicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'legacy_id'                   => $this->legacy_id,
            'code'                        => $this->code,
            'name'                        => $this->name,
            'service_type'                => $this->service_type?->value,
            'service_type_label'          => $this->service_type?->label(),
            'description'                 => $this->description,
            'is_visible'                  => $this->is_visible,
            'is_online_visible'           => $this->is_online_visible,
            'quota'                       => $this->quota,
            'jkn_quota'                   => $this->jkn_quota,
            'bpjs_code'                   => $this->bpjs_code,
            'is_active'                   => $this->is_active,
            // satusehat_code intentionally excluded from user-facing form
            // but still exists in DB for integration modules
            'warehouse_pending'           => true, // Master Gudang belum tersedia
            'created_at'                  => $this->created_at,
            'updated_at'                  => $this->updated_at,
        ];
    }
}
