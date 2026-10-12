<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // If employee_id is null, it means creating a new Employee
            'employee_id' => ['nullable', 'integer', 'exists:employees,id', 'unique:doctors,employee_id'],

            // Person/Employee fields
            'person' => ['required', 'array'],
            'person.national_id' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('employees', 'national_id')->ignore($this->employee_id),
            ],
            'person.ihs_number' => ['nullable', 'string', 'max:255'],
            'person.name' => ['required', 'string', 'max:255'],
            'person.birth_place' => ['nullable', 'string', 'max:255'],
            'person.birth_date' => ['nullable', 'date'],
            'person.gender' => ['required', 'in:L,P'],
            'person.nationality' => ['nullable', 'in:WNI,WNA'],
            'person.blood_type' => ['nullable', 'in:A,B,AB,O,Unknown'],
            'person.allergies' => ['nullable', 'string'],
            'person.religion' => ['nullable', 'string', 'max:50'],
            'person.marital_status' => ['nullable', 'string', 'max:50'],
            'person.address' => ['nullable', 'string'],
            'person.postal_code' => ['nullable', 'string', 'max:10'],
            'person.province_id' => ['nullable', 'string', 'max:10'],
            'person.regency_id' => ['nullable', 'string', 'max:10'],
            'person.district_id' => ['nullable', 'string', 'max:10'],
            'person.village_id' => ['nullable', 'string', 'max:15'],
            'person.phone' => ['nullable', 'string', 'max:20'],
            'person.education_id' => ['nullable', 'integer'],
            'person.occupation_id' => ['nullable', 'integer'],

            // Professional/Doctor Profile fields
            'professional' => ['required', 'array'],
            'professional.specialization_id' => ['required', 'integer', 'exists:specializations,id'],
            'professional.str_number' => ['nullable', 'string', 'max:50'],
            'professional.sip_number' => [
                'nullable',
                'string',
                'max:255',
                'unique:doctors,sip_number',
            ],
            'professional.sip_valid_until' => ['nullable', 'date'],
            'professional.bpjs_dpjp_code' => ['nullable', 'string', 'max:50'],
            'professional.is_active' => ['boolean'],

            // Signature file
            'signature' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }
}
