<?php

namespace App\Http\Requests\Api\V1\MasterData;

use App\Models\LetterType;
use Illuminate\Foundation\Http\FormRequest;

class StoreLetterTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LetterType::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:letter_types,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama surat wajib diisi.',
            'name.string' => 'Nama surat harus berupa teks.',
            'name.max' => 'Nama surat maksimal 255 karakter.',
            'name.unique' => 'Nama surat sudah digunakan.',
            'is_active.boolean' => 'Status harus berupa true atau false.',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->name),
            ]);
        }
    }
}
