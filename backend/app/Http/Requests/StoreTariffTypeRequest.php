<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTariffTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:tariff_types,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'components' => 'required|array|min:1',
            'components.*.tariff_component_id' => 'required|integer|exists:tariff_components,id|distinct',
            'components.*.percentage' => 'required|numeric|min:0|max:100',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'name' => $this->name ? preg_replace('/\s+/', ' ', trim($this->name)) : null,
            'code' => $this->code ? strtoupper(trim($this->code)) : null,
            'description' => $this->description ? trim($this->description) : null,
        ]);
    }
}
