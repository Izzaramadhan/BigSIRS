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
            'name' => $this->employee ? $this->employee->name : null,
            'full_name' => $this->employee ? $this->employee->name : null,
            'specialization' => $this->specialization ? [
                'id' => $this->specialization->id,
                'name' => $this->specialization->name,
            ] : null,
            'is_active' => (bool) $this->is_active,
        ];

        if ($isDetail) {
            $data = array_merge($data, [
                'legacy_id' => $this->legacy_id,
                'employee_id' => $this->employee_id,
                'specialization_id' => $this->specialization_id,
                'str_number' => $this->str_number,
                'sip_number' => $this->sip_number,
                'sip_valid_until' => $this->sip_valid_until ? $this->sip_valid_until->format('Y-m-d') : null,
                'employee' => $this->employee ? [
                    'id' => $this->employee->id,
                    'national_id' => $this->employee->national_id,
                    'ihs_number' => $this->employee->ihs_number,
                    'name' => $this->employee->name,
                    'birth_place' => $this->employee->birth_place,
                    'birth_date' => $this->employee->birth_date ? $this->employee->birth_date->format('Y-m-d') : null,
                    'gender' => $this->employee->gender,
                    'nationality' => $this->employee->nationality,
                    'blood_type' => $this->employee->blood_type,
                    'allergies' => $this->employee->allergies,
                    'religion' => $this->employee->religion,
                    'marital_status' => $this->employee->marital_status,
                    'address' => $this->employee->address,
                    'postal_code' => $this->employee->postal_code,
                    'province_id' => $this->employee->province_id,
                    'regency_id' => $this->employee->regency_id,
                    'district_id' => $this->employee->district_id,
                    'village_id' => $this->employee->village_id,
                    'phone' => $this->employee->phone,
                    'education_id' => $this->employee->education_id,
                    'occupation_id' => $this->employee->occupation_id,
                ] : null,
                'bpjs_dpjp_code' => $this->bpjs_dpjp_code,
                'has_signature' => ! empty($this->signature_path),
                'signature_url' => $this->signature_path ? Storage::disk('public')->url($this->signature_path) : null,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ]);
        }

        return $data;
    }
}
