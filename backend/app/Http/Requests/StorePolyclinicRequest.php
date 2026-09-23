<?php

namespace App\Http\Requests;

use App\Enums\ServiceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePolyclinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->code !== null ? strtoupper(trim($this->code)) : null,
            'name' => $this->name !== null ? preg_replace('/\s+/', ' ', trim($this->name)) : null,
            'bpjs_code' => $this->bpjs_code !== null ? (trim($this->bpjs_code) === '' ? null : trim($this->bpjs_code)) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code'             => ['required', 'string', 'max:30', 'unique:polyclinics,code'],
            'name'             => ['required', 'string', 'max:150'],
            'service_type'     => ['required', Rule::enum(ServiceType::class)],
            'description'      => ['nullable', 'string', 'max:255'],
            'is_visible'       => ['boolean'],
            'is_online_visible' => ['boolean'],
            'quota'            => ['integer', 'min:0'],
            'jkn_quota'        => ['integer', 'min:0'],
            'bpjs_code'        => ['nullable', 'string', 'max:50'],
            'is_active'        => ['boolean'],
            // parent_id and satusehat_code not accepted from user form
        ];
    }
}
