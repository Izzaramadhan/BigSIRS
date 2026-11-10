<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuildingRequest extends FormRequest
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
        $isLegacy = LegacyAccess::isAvailable('ref_gedung');
        $uniqueRule = $isLegacy
            ? Rule::unique('legacy.ref_gedung', 'nama')->whereNull('deleted_at')
            : Rule::unique('buildings', 'name')->whereNull('deleted_at');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueRule,
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama gedung wajib diisi.',
            'name.unique' => 'Nama gedung sudah digunakan.',
        ];
    }
}
