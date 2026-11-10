<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreDistrictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'name' => $this->name ? Str::squish(trim($this->name)) : null,
            'code' => $this->code ? trim($this->code) : null,
        ]);
    }

    public function rules(): array
    {
        $isLegacy = LegacyAccess::isAvailable('ref_kecamatan');

        $regencyExistsRule = $isLegacy
            ? Rule::exists('legacy.ref_kabupaten', 'id')->whereNull('deleted_at')
            : Rule::exists('regencies', 'id')->whereNull('deleted_at');

        $uniqueCodeRule = $isLegacy
            ? Rule::unique('legacy.ref_kecamatan', 'kode_kecamatan')->whereNull('deleted_at')
            : Rule::unique('districts', 'code')->whereNull('deleted_at');

        $uniqueNameRule = $isLegacy
            ? Rule::unique('legacy.ref_kecamatan', 'nama')
                ->where('id_kabupaten', $this->regency_id)
                ->whereNull('deleted_at')
            : Rule::unique('districts', 'name')
                ->where('regency_id', $this->regency_id)
                ->whereNull('deleted_at');

        return [
            'regency_id' => [
                'required',
                $regencyExistsRule,
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                $uniqueCodeRule,
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueNameRule,
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
