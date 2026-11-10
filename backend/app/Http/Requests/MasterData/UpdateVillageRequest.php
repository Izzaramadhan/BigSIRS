<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVillageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('village')?->id ?? $this->route('village');
        $isLegacy = LegacyAccess::isAvailable('ref_kelurahan');

        $districtExistsRule = $isLegacy
            ? Rule::exists('legacy.ref_kecamatan', 'id')->where(fn ($q) => $q->whereNull('deleted_at'))
            : Rule::exists('districts', 'id')->where(fn ($q) => $q->whereNull('deleted_at'));

        $uniqueCodeRule = $isLegacy
            ? Rule::unique('legacy.ref_kelurahan', 'kode_kelurahan')->ignore($id)
            : Rule::unique('villages', 'code')->ignore($id);

        return [
            'district_id' => [
                'required',
                $districtExistsRule,
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                $uniqueCodeRule,
            ],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim(preg_replace('/\s+/', ' ', $this->name)),
            ]);
        }
    }
}
