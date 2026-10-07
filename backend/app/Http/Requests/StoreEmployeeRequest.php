<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:20', 'unique:employees,national_id'],
            'ihs_number' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:L,P'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'blood_type' => ['nullable', 'in:A,B,AB,O,Unknown'],
            'religion' => ['nullable', 'string', 'max:255'],
            'marital_status' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'string', 'max:10'],
            'regency_id' => ['nullable', 'string', 'max:10'],
            'district_id' => ['nullable', 'string', 'max:10'],
            'village_id' => ['nullable', 'string', 'max:15'],
            'phone' => ['nullable', 'string', 'max:255'],
            'education_id' => ['nullable', 'exists:educations,id'],
            'occupation_id' => ['nullable', 'exists:occupations,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'profession' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
