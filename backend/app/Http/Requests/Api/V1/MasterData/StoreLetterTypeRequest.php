<?php

namespace App\Http\Requests\Api\V1\MasterData;

use App\Models\Legacy\LegacyLetterType;
use App\Models\LetterType;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $class = LegacyAccess::isAvailable('ref_jenis_surat') ? LegacyLetterType::class : LetterType::class;
        return $this->user()->can('create', $class);
    }

    public function rules(): array
    {
        $uniqueRule = LegacyAccess::isAvailable('ref_jenis_surat')
            ? Rule::unique('legacy.ref_jenis_surat', 'nama')->whereNull('deleted_at')
            : Rule::unique('letter_types', 'name')->whereNull('deleted_at');

        return [
            'name' => ['required', 'string', 'max:50', $uniqueRule],
            'description' => 'nullable|string|max:255',
            'legacy_resource' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama surat wajib diisi.',
            'name.string' => 'Nama surat harus berupa teks.',
            'name.max' => 'Nama surat maksimal 50 karakter.',
            'name.unique' => 'Nama surat sudah digunakan.',
            'legacy_resource.string' => 'Resource harus berupa teks.',
            'legacy_resource.max' => 'Resource maksimal 255 karakter.',
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
