<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Izinkan NIK 8-16 digit saat update agar data hasil masking legacy tidak terblokir bila tidak diubah
            'nik' => ['nullable', 'regex:/^\d{8,16}$/'],
            'ihs_number' => ['nullable', 'string', 'max:255'],
            'name' => ['sometimes', 'required', 'string', 'max:50'],
            'birth_place' => ['sometimes', 'required', 'string', 'max:50'],
            'birth_date' => ['sometimes', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gender' => ['sometimes', 'required', Rule::in(['L', 'P'])],
            'nationality' => ['sometimes', 'required', Rule::in(['WNI', 'WNA'])],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'province_id' => ['nullable', 'string', 'max:20'],
            'regency_id' => ['nullable', 'string', 'max:20'],
            'district_id' => ['nullable', 'string', 'max:20'],
            'village_id' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'phone' => ['sometimes', 'required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:50'],
            'education_id' => ['nullable', 'integer'],
            'occupation_id' => ['nullable', 'integer'],
            'blood_type' => ['nullable', 'string'],
            'religion' => ['nullable', 'string'],
            'marital_status' => ['nullable', 'string'],
            'guarantor_id' => ['sometimes', 'required', 'integer'],
            'insurance_number' => ['nullable', 'string', 'max:50'],
            'hak_kelas' => ['nullable', 'string', 'max:50'],
            'has_allergy' => ['nullable', 'string'],
            'allergy_notes' => ['nullable', 'string'],
            'is_fast_track' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'array'],
            'emergency_contact.name' => ['nullable', 'string', 'max:255'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact.address' => ['nullable', 'string'],
        ];
    }
}

