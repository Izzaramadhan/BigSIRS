<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcedureCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => is_string($this->name) ? preg_replace('/\s+/', ' ', trim($this->name)) : $this->name,
            ]);
        }
        
        if ($this->has('description')) {
            $this->merge([
                'description' => is_string($this->description) && trim($this->description) !== '' 
                    ? preg_replace('/\s+/', ' ', trim($this->description)) 
                    : null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
