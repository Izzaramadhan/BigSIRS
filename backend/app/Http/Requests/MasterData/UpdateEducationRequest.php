<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $name = trim(preg_replace('/\s+/', ' ', $this->name));
            $this->merge(['name' => $name]);
        }
    }
}
