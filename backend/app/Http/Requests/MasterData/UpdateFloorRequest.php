<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFloorRequest extends FormRequest
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
        $floorParam = $this->route('floor');
        $floorId = is_object($floorParam) ? $floorParam->id : $floorParam;

        $isLegacy = LegacyAccess::isAvailable('ref_lantai');
        $buildingTable = $isLegacy ? 'legacy.ref_gedung' : 'buildings';

        $uniqueRule = $isLegacy
            ? Rule::unique('legacy.ref_lantai', 'nama')->where('id_gedung', $this->building_id)->ignore($floorId)->whereNull('deleted_at')
            : Rule::unique('floors', 'name')->where('building_id', $this->building_id)->ignore($floorId)->whereNull('deleted_at');

        return [
            'building_id' => [
                'required',
                'integer',
                Rule::exists($buildingTable, 'id')->whereNull('deleted_at'),
            ],
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
            'building_id.required' => 'Gedung wajib dipilih.',
            'building_id.exists' => 'Gedung yang dipilih tidak valid atau sudah dihapus.',
            'name.required' => 'Nama lantai wajib diisi.',
            'name.unique' => 'Nama lantai sudah digunakan pada gedung yang dipilih.',
        ];
    }
}
