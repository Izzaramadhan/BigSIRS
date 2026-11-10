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
        $uniqueRule = LegacyAccess::isAvailable('ref_provinsi')
            ? Rule::unique('legacy.ref_provinsi', 'nama')->whereNull('deleted_at')
            : Rule::unique('provinces', 'name')->whereNull('deleted_at');

        return [
            'name' => ['required', 'string', 'max:255', $uniqueRule],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
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

