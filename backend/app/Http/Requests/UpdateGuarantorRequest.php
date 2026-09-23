<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGuarantorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->code !== null ? strtoupper(trim($this->code)) : null,
            'name' => $this->name !== null ? preg_replace('/\s+/', ' ', trim($this->name)) : null,
        ]);
        
        foreach (['legacy_id', 'code', 'name'] as $field) {
            if ($this->has($field) && trim((string)$this->get($field)) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('guarantor')->id ?? $this->route('guarantor');
        return [
            'code' => ['required', 'string', 'max:30', 'unique:guarantors,code,' . $id],
            'name' => ['required', 'string', 'max:150'],
            'legacy_id' => ['nullable', 'integer', 'unique:guarantors,legacy_id,' . $id],
            'type' => ['required', new \Illuminate\Validation\Rules\Enum(\App\Enums\GuarantorType::class)],
            'is_active' => ['boolean'],
        ];
    }
}
