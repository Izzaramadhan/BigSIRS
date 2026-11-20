<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;

class SearchPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('q') && is_string($this->input('q'))) {
            $trimmed = trim($this->input('q'));
            $this->merge([
                'q' => $trimmed === '' ? null : $trimmed,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
            'birth_date' => ['nullable', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'guarantor' => ['nullable', 'string', 'max:50'],
            'guarantor_id' => ['nullable'],
        ];
    }
}

