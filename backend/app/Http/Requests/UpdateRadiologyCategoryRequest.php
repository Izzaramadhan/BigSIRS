<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRadiologyCategoryRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('radiology_categories', 'name')->ignore($this->route('radiology_category')),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'loinc_code' => ['nullable', 'string', 'max:255'],
            'loinc_url' => ['nullable', 'string', 'max:255'],
            'snomed_code' => ['nullable', 'string', 'max:255'],
            'snomed_url' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (is_string($this->name)) {
            $merge['name'] = trim(preg_replace('/\s+/', ' ', $this->name));
        }

        foreach (['description', 'loinc_code', 'loinc_url', 'snomed_code', 'snomed_url'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $val = trim($this->input($field));
                $merge[$field] = $val === '' ? null : $val;
            }
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }
}
