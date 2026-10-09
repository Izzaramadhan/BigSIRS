<?php

namespace App\Http\Requests\Api\V1\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLetterTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('letter_type'));
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('letter_types', 'name')->ignore($this->route('letter_type')),
            ],
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
