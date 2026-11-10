<?php

namespace App\Http\Requests\Api\V1\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDietTypeRequest extends FormRequest
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
        $uniqueRule = LegacyAccess::isAvailable('ref_jenis_diet')
            ? Rule::unique('legacy.ref_jenis_diet', 'nama')->whereNull('deleted_at')
            : Rule::unique('diet_types', 'name')->whereNull('deleted_at');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                $uniqueRule,
            ],
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama asuhan gizi wajib diisi.',
            'name.unique' => 'Nama asuhan gizi sudah digunakan.',
            'name.max' => 'Nama asuhan gizi tidak boleh lebih dari 50 karakter.',
            'description.max' => 'Deskripsi tidak boleh lebih dari 255 karakter.',
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
