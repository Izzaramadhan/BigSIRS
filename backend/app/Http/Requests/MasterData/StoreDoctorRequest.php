<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id', 'unique:doctors,employee_id'],
            'specialization_id' => ['nullable', 'integer', 'exists:specializations,id'],
            'str_number' => ['nullable', 'string', 'max:50'],
            'sip_number' => ['nullable', 'string', 'max:255', 'unique:doctors,sip_number'],
            'sip_valid_until' => ['nullable', 'date'],
            'bpjs_dpjp_code' => ['nullable', 'string', 'max:50'],
            'ihs_number' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'signature' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }
}
