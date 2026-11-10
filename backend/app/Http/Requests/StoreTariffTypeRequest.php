<?php

namespace App\Http\Requests;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTariffTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isLegacy = LegacyAccess::isAvailable('ref_jenis_tarif');
        $codeRule = $isLegacy
            ? Rule::unique('legacy.ref_jenis_tarif', 'kode')->whereNull('deleted_at')
            : Rule::unique('tariff_types', 'code');
        $componentTable = $isLegacy ? 'legacy.ref_komponen' : 'tariff_components';

        return [
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', $codeRule],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'components' => 'required|array|min:1',
            'components.*.tariff_component_id' => ['required', 'integer', "exists:{$componentTable},id", 'distinct'],
            'components.*.percentage' => 'required|numeric|min:0|max:100',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'name' => $this->name ? preg_replace('/\s+/', ' ', trim($this->name)) : null,
            'code' => $this->code ? strtoupper(trim($this->code)) : null,
            'description' => $this->description ? trim($this->description) : null,
        ]);
    }
}
