<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcedureCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'name' => is_string($this->name) ? preg_replace('/\s+/', ' ', trim($this->name)) : $this->name,
            'description' => is_string($this->description) && trim($this->description) !== '' 
                ? preg_replace('/\s+/', ' ', trim($this->description)) 
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
