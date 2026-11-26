<?php

namespace App\Http\Requests\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProvinceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('province')?->id ?? $this->route('province');
        $isLegacy = LegacyAccess::isAvailable('ref_provinsi');

        $uniqueRule = $isLegacy
            ? Rule::unique('legacy.ref_provinsi', 'nama')->whereNull('deleted_at')->ignore($id)
            : Rule::unique('provinces', 'name')->ignore($id);

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

