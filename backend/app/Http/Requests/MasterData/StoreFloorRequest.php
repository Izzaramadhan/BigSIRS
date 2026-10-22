<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->name !== null ? preg_replace('/\s+/', ' ', trim((string) $this->name)) : null,
            'description' => $this->description !== null ? trim((string) $this->description) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'building_id' => [
                'required',
                'integer',
                Rule::exists('buildings', 'id')->whereNull('deleted_at'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('floors', 'name')
                    ->where('building_id', $this->building_id)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'building_id.required' => 'Gedung wajib dipilih.',
            'building_id.exists' => 'Gedung yang dipilih tidak valid atau sudah dihapus.',
            'name.required' => 'Nama lantai wajib diisi.',
            'name.unique' => 'Nama lantai sudah digunakan pada gedung yang dipilih.',
        ];
    }
}
