<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'required', 
                'integer', 
                'exists:employees,id', 
                Rule::unique('doctors', 'employee_id')->ignore($this->route('doctor'))
            ],
            'specialization_id' => ['nullable', 'integer', 'exists:specializations,id'],
            'str_number' => ['nullable', 'string', 'max:50'],
            'sip_number' => [
                'nullable', 
                'string', 
                'max:255', 
                Rule::unique('doctors', 'sip_number')->ignore($this->route('doctor'))
            ],
            'sip_valid_until' => ['nullable', 'date'],
            'bpjs_dpjp_code' => ['nullable', 'string', 'max:50'],
            'ihs_number' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'signature' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'remove_signature' => ['nullable', 'boolean'],
        ];
    }
}
