<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uniqueCodeRule = LegacyAccess::isAvailable('ref_kabupaten')
            ? Rule::unique('legacy.ref_kabupaten', 'kode_kabupaten')->whereNull('deleted_at')
            : Rule::unique('regencies', 'code')->whereNull('deleted_at');

        $provinceExistsRule = LegacyAccess::isAvailable('ref_provinsi')
            ? Rule::exists('legacy.ref_provinsi', 'id')->whereNull('deleted_at')
            : Rule::exists('provinces', 'id')->whereNull('deleted_at');

        return [
            'code' => ['nullable', 'string', 'max:50', $uniqueCodeRule],
            'name' => ['required', 'string', 'max:255'],
            'province_id' => ['required', $provinceExistsRule],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
