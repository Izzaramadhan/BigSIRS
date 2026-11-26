<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProvinceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isLegacy = LegacyAccess::isAvailable('ref_provinsi');
        $uniqueRule = $isLegacy
            ? Rule::unique('legacy.ref_provinsi', 'nama')->whereNull('deleted_at')
            : Rule::unique('provinces', 'name');

        return [
            'name' => ['required', 'string', 'max:255', $uniqueRule],
            'code' => $isLegacy ? ['nullable', 'string', 'max:50'] : ['prohibited'],
            'is_active' => $isLegacy ? ['nullable', 'boolean'] : ['prohibited'],
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

