<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRadiologyCategoryRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', 'unique:radiology_categories,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'loinc_code' => ['nullable', 'string', 'max:255'],
            'loinc_url' => ['nullable', 'string', 'max:255'],
            'snomed_code' => ['nullable', 'string', 'max:255'],
            'snomed_url' => ['nullable', 'string', 'max:255'],
        ];
    }
}
