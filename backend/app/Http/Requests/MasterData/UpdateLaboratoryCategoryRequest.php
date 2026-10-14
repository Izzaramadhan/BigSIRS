<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLaboratoryCategoryRequest extends FormRequest
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
        $id = $this->route('laboratory_category')?->id ?? $this->route('laboratory_category');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:laboratory_categories,name,'.$id],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['nullable', 'string', 'in:lab klinik,lab gigi,lab mikrobakteri'],
            'loinc_code' => ['nullable', 'string', 'max:255'],
            'loinc_url' => ['nullable', 'string', 'url', 'max:255'],
            'snomed_code' => ['nullable', 'string', 'max:255'],
            'snomed_url' => ['nullable', 'string', 'url', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'snomed_url.url' => 'Format SNOMED URL tidak valid.',
            'loinc_url.url' => 'Format LOINC URL tidak valid.',
        ];
    }

    public function prepareForValidation()
    {
        $merge = [];
        if ($this->has('name')) {
            $merge['name'] = trim(preg_replace('/\s+/', ' ', (string) $this->name));
        }
        if ($this->has('snomed_url')) {
            $merge['snomed_url'] = is_string($this->snomed_url) ? trim($this->snomed_url) : $this->snomed_url;
            if ($merge['snomed_url'] === '') {
                $merge['snomed_url'] = null;
            }
        }
        if ($this->has('loinc_url')) {
            $merge['loinc_url'] = is_string($this->loinc_url) ? trim($this->loinc_url) : $this->loinc_url;
            if ($merge['loinc_url'] === '') {
                $merge['loinc_url'] = null;
            }
        }
        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}
