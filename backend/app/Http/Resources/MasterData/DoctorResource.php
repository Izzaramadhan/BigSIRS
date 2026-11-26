<?php

namespace App\Http\Resources\MasterData;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isDetail = in_array($request->route()?->getActionMethod(), ['show', 'store', 'update'], true);

        $data = [
            'id' => $this->id,
            'name' => $this->employee ? $this->employee->name : ($this->name ?? null),
            'full_name' => $this->employee ? $this->employee->name : ($this->full_name ?? $this->name ?? null),
            'specialization' => $this->specialization ? [
                'id' => $this->specialization->id,
                'name' => $this->specialization->name,
            ] : null,
            'is_active' => (bool) $this->is_active,
        ];

        if ($isDetail) {
            $personModel = $this->relationLoaded('person') ? $this->person : null;
            $legacyDoctorIhs = $this->ihs_id ?? $this->ihs_number ?? null;

            $data = array_merge($data, [
                'legacy_id' => $this->legacy_id,
                'employee_id' => $this->employee_id,
                'specialization_id' => $this->specialization_id,
                'str_number' => $this->str_number,
                'sip_number' => $this->sip_number,
                'sip_valid_until' => $this->sip_valid_until ? $this->sip_valid_until->format('Y-m-d') : null,
                'employee' => $this->employee ? $this->serializePersonPayload($this->employee, $legacyDoctorIhs, true) : null,
                'person' => $personModel ? $this->serializePersonPayload($personModel, $legacyDoctorIhs, false) : null,
                'bpjs_dpjp_code' => $this->bpjs_dpjp_code,
                'has_signature' => ! empty($this->signature_path),
                'signature_url' => $this->signature_path ? Storage::disk('public')->url($this->signature_path) : null,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ]);
        }

        return $data;
    }

    private function serializePersonPayload($source, ?string $doctorIhsNumber, bool $includeId): array
    {
        $payload = [
            'national_id' => $source->national_id,
            'ihs_number' => $source->ihs_number ?? $doctorIhsNumber,
            'name' => $source->name,
            'birth_place' => $source->birth_place,
            'birth_date' => $source->birth_date ? $source->birth_date->format('Y-m-d') : null,
            'gender' => $source->gender,
            'nationality' => $source->nationality,
            'blood_type' => $source->blood_type,
            'allergies' => $source->allergies,
            'religion' => $source->religion,
            'marital_status' => $source->marital_status,
            'address' => $source->address,
            'postal_code' => $source->postal_code,
            'province_id' => $source->province_id,
            'regency_id' => $source->regency_id,
            'district_id' => $source->district_id,
            'village_id' => $source->village_id,
            'phone' => $source->phone,
            'education_id' => $source->education_id,
            'occupation_id' => $source->occupation_id,
        ];

        if ($includeId) {
            return ['id' => $source->id] + $payload;
        }

        return $payload;
    }
}
