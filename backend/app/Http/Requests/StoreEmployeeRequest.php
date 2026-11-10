<?php

namespace App\Http\Requests;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isLegacy = LegacyAccess::isAvailable('ref_pegawai');
        $educationTable = $isLegacy ? 'legacy.ref_pendidikan' : 'educations';
        $occupationTable = $isLegacy ? 'legacy.ref_pekerjaan' : 'occupations';
        $positionTable = $isLegacy ? 'legacy.ref_jabatan' : 'positions';

        $nationalIdRule = ['nullable', 'string', 'max:20'];
        if ($isLegacy) {
            $nationalIdRule[] = 'unique:legacy.master_person_index,no_ktp';
        } else {
            $nationalIdRule[] = 'unique:employees,national_id';
        }

        return [
            'code' => ['nullable', 'string', 'max:255'],
            'national_id' => $nationalIdRule,
            'ihs_number' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:L,P'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'blood_type' => ['nullable', 'in:A,B,AB,O,Unknown'],
            'allergies' => ['nullable', 'string'],
            'religion' => ['nullable', 'string', 'max:255'],
            'marital_status' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'string', 'max:10'],
            'regency_id' => ['nullable', 'string', 'max:10'],
            'district_id' => ['nullable', 'string', 'max:10'],
            'village_id' => ['nullable', 'string', 'max:15'],
            'phone' => ['nullable', 'string', 'max:255'],
            'education_id' => ['nullable', "exists:{$educationTable},id"],
            'occupation_id' => ['nullable', "exists:{$occupationTable},id"],
            'position_id' => ['nullable', "exists:{$positionTable},id"],
            'profession' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'signature' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'signature.image' => 'File tanda tangan harus berupa gambar.',
            'signature.mimes' => 'Format tanda tangan harus berupa jpeg, png, atau jpg.',
            'signature.max' => 'Ukuran tanda tangan maksimal 2MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'signature' => 'tanda tangan',
        ];
    }
}
