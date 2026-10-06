<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRegencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', 'unique:regencies,code,' . $this->route('regency')->id],
            'name' => ['required', 'string', 'max:255'],
            'province_id' => ['required', 'exists:provinces,id'],
            'is_active' => ['boolean'],
        ];
    }
}
