<?php

namespace App\Http\Requests\Admission;

use App\Models\Legacy\LegacyAdmission;
use App\Support\GuarantorValidation;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit_id' => ['nullable', 'integer'],
            'guarantor_id' => ['required', 'integer'],
            'insurance_number' => ['nullable', 'string', 'max:50'],
            'referral_source_id' => ['nullable', 'integer'],
            'referral_source' => ['nullable', 'string', 'max:255'],
            'referral_name' => ['nullable', 'string', 'max:255'],
            'polyclinic_id' => ['required', 'integer'],
            'doctor_id' => ['required', 'integer'],
            'case_type' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'family_name' => ['nullable', 'string', 'max:255'],
            'family_relationship' => ['nullable', 'string', 'max:100'],
            'family_phone' => ['nullable', 'string', 'max:50'],
            'family_address' => ['nullable', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $polyclinicId = $this->input('polyclinic_id');
            $doctorId = $this->input('doctor_id');

            if (!empty($polyclinicId) && !empty($doctorId)) {
                $isAssigned = false;
                if (LegacyAccess::isAvailable('trx_jadwal_dokter') && LegacyAccess::hasTable('trx_jadwal_dokter')) {
                    $isAssigned = DB::connection('legacy')->table('trx_jadwal_dokter')
                        ->where('id_poliklinik', $polyclinicId)
                        ->where('id_dokter', $doctorId)
                        ->where('status', '1')
                        ->where('libur', 0)
                        ->exists();
                } elseif (Schema::hasTable('doctor_schedules')) {
                    $isAssigned = \App\Models\MasterData\DoctorSchedule::where('polyclinic_id', $polyclinicId)
                        ->where('doctor_id', $doctorId)
                        ->where('is_holiday', false)
                        ->exists();
                } else {
                    $isAssigned = true;
                }

                if (!$isAssigned) {
                    $validator->errors()->add('doctor_id', 'Dokter yang dipilih tidak bertugas atau tidak memiliki jadwal aktif pada poliklinik ini.');
                }
            }

            $currentGuarantorId = null;
            $admissionId = $this->route('id');
            if ($admissionId && LegacyAccess::isAvailable('trx_admisi')) {
                $current = LegacyAdmission::find($admissionId);
                $currentGuarantorId = $current?->id_jenis_asuransi ? (int) $current->id_jenis_asuransi : null;
            }

            GuarantorValidation::validateGuarantorId($validator, $this->input('guarantor_id'), $currentGuarantorId);
        });
    }

    public function messages(): array
    {
        return [
            'guarantor_id.required' => 'Jenis Asuransi wajib dipilih.',
            'polyclinic_id.required' => 'Klinik tujuan wajib dipilih.',
            'doctor_id.required' => 'Dokter Klinik wajib dipilih.',
        ];
    }
}
