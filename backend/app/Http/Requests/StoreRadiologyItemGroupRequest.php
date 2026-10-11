<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRadiologyItemGroupRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (is_string($this->name)) {
            $merge['name'] = trim(preg_replace('/\s+/', ' ', $this->name));
        }

        if ($this->has('description') && is_string($this->input('description'))) {
            $val = trim($this->input('description'));
            $merge['description'] = $val === '' ? null : $val;
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }
}
