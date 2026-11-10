<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateDistrictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => $this->name ? Str::squish(trim($this->name)) : null,
            ]);
        }
        if ($this->has('code')) {
            $this->merge([
                'code' => $this->code ? trim($this->code) : null,
            ]);
        }
    }

    public function rules(): array
    {
        $districtId = $this->route('district')?->id ?? $this->route('district');
        $isLegacy = LegacyAccess::isAvailable('ref_kecamatan');

        $regencyExistsRule = $isLegacy
            ? Rule::exists('legacy.ref_kabupaten', 'id')->whereNull('deleted_at')
            : Rule::exists('regencies', 'id')->whereNull('deleted_at');

        $uniqueCodeRule = $isLegacy
            ? Rule::unique('legacy.ref_kecamatan', 'kode_kecamatan')
                ->ignore($districtId)
                ->whereNull('deleted_at')
            : Rule::unique('districts', 'code')
                ->ignore($districtId)
                ->whereNull('deleted_at');

        $uniqueNameRule = $isLegacy
            ? Rule::unique('legacy.ref_kecamatan', 'nama')
                ->where('id_kabupaten', $this->regency_id)
                ->ignore($districtId)
                ->whereNull('deleted_at')
            : Rule::unique('districts', 'name')
                ->where('regency_id', $this->regency_id)
                ->ignore($districtId)
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
