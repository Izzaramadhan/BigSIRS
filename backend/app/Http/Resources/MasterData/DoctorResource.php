<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isDetail = $request->route()->getActionMethod() === 'show' || $request->route()->getActionMethod() === 'store' || $request->route()->getActionMethod() === 'update';

        $data = [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->employee ? $this->employee->name : null,
            'nik' => $this->employee ? $this->employee->code : null, // Assuming Employee code holds NIK
            'specialization_id' => $this->specialization_id,
            'specialization' => $this->specialization ? $this->specialization->name : null,
            'is_active' => $this->is_active,
        ];

        if ($isDetail) {
            $data = array_merge($data, [
                'str_number' => $this->str_number,
                'sip_number' => $this->sip_number,
                'sip_valid_until' => $this->sip_valid_until ? $this->sip_valid_until->format('Y-m-d') : null,
                'bpjs_dpjp_code' => $this->bpjs_dpjp_code,
                'ihs_number' => $this->ihs_number,
                'has_signature' => !empty($this->signature_path),
                'signature_url' => $this->signature_path ? Storage::disk('public')->url($this->signature_path) : null,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ]);
        }

        return $data;
    }
}
