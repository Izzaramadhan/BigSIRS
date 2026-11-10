<?php

namespace App\Http\Requests\Api\V1\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityTypeRequest extends FormRequest
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
        $isLegacy = LegacyAccess::isAvailable('ref_jenis_kegiatan');
        $uniqueNameRule = $isLegacy
            ? Rule::unique('legacy.ref_jenis_kegiatan', 'nama')->whereNull('deleted_at')
            : Rule::unique('activity_types', 'name')->whereNull('deleted_at');

        $parentExistsRule = $isLegacy
            ? Rule::exists('legacy.ref_jenis_kegiatan', 'id')->whereNull('deleted_at')
            : Rule::exists('activity_types', 'id')->whereNull('deleted_at');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                $uniqueNameRule,
            ],
            'parent_id' => [
                'nullable',
                'integer',
                $parentExistsRule,
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jenis kegiatan wajib diisi.',
            'name.unique' => 'Nama jenis kegiatan sudah digunakan.',
            'name.max' => 'Nama jenis kegiatan tidak boleh lebih dari 100 karakter.',
            'parent_id.exists' => 'Induk jenis kegiatan tidak ditemukan atau sudah dihapus.',
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
