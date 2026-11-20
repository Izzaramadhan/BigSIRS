<?php

namespace App\Http\Requests\Admission;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer'],
            'polyclinic_id' => ['nullable', 'integer'],
            'doctor_id' => ['nullable', 'integer'],
            'polyclinic' => ['nullable', 'string', 'max:255'],
            'doctor' => ['nullable', 'string', 'max:255'],
            'guarantor_id' => ['nullable', 'integer'],
            'insurance_number' => ['nullable', 'string', 'max:50'],
            'nik' => ['nullable', 'string', 'max:25'],
            'referral_source_id' => ['nullable', 'integer'],
            'referral_source' => ['nullable', 'string', 'max:255'],
            'referral_name' => ['nullable', 'string', 'max:255'],
            'visit_type' => ['nullable', 'string', 'max:50'],
            'case_type' => ['nullable', 'string', 'max:100'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'kode_booking' => ['nullable', 'string', 'max:50'],
            'satusehat_consent' => ['nullable', 'string', 'max:50'],
            'admission_datetime' => ['nullable', 'string', 'max:50'],
            'admission_date' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
            'family_name' => ['nullable', 'string', 'max:255'],
            'family_relationship' => ['nullable', 'string', 'max:100'],
            'family_phone' => ['nullable', 'string', 'max:50'],
            'family_address' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'array'],
            'emergency_contact.name' => ['nullable', 'string', 'max:255'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact.address' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $polyclinicId = $this->input('polyclinic_id');
            $polyclinic = $this->input('polyclinic');
            if (empty($polyclinicId) && empty($polyclinic)) {
                $validator->errors()->add('polyclinic_id', 'Klinik / Poliklinik tujuan wajib dipilih.');
            }

            $doctorId = $this->input('doctor_id');
            $doctor = $this->input('doctor');
            if (empty($doctorId) && empty($doctor)) {
                $validator->errors()->add('doctor_id', 'Dokter Klinik wajib dipilih.');
            }

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
        });
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Data pasien wajib dipilih.',
            'patient_id.integer' => 'ID pasien tidak valid.',
            'polyclinic_id.required' => 'Klinik / Poliklinik tujuan wajib dipilih.',
            'doctor_id.required' => 'Dokter Klinik wajib dipilih.',
        ];
    }
}
