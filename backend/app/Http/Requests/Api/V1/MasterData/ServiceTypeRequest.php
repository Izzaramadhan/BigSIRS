<?php

namespace App\Http\Requests\Api\V1\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('service_type') ? $this->route('service_type')->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_types', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama jenis layanan',
            'is_active' => 'status aktif',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama jenis layanan wajib diisi.',
            'name.unique' => 'Nama jenis layanan sudah digunakan.',
        ];
    }
}
