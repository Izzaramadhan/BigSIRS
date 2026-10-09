<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLaboratoryCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:laboratory_categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['nullable', 'string', 'in:lab klinik,lab gigi,lab mikrobakteri'],
            'loinc_code' => ['nullable', 'string', 'max:255'],
            'loinc_url' => ['nullable', 'string', 'url', 'max:255'],
            'snomed_code' => ['nullable', 'string', 'max:255'],
            'snomed_url' => ['nullable', 'string', 'url', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim(preg_replace('/\s+/', ' ', $this->name)),
            ]);
        }
    }
}
