<?php

namespace App\Http\Requests\Api\V1\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class StoreIcd9cmCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:icd9_cms,code',
            'name' => 'required|string|max:255',
            'english_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'inacbg_code' => 'nullable|string|max:100',
            'inacbg_name' => 'nullable|string',
        ];
    }
}
