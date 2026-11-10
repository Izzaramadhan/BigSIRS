<?php

namespace App\Http\Requests;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTariffTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tariffType = $this->route('tariff_type');
        $tariffTypeId = is_object($tariffType) ? $tariffType->id : $tariffType;
        $code = is_object($tariffType) ? $tariffType->code : null;

        $isLegacy = LegacyAccess::isAvailable('ref_jenis_tarif');
        $codeRule = $isLegacy
            ? Rule::unique('legacy.ref_jenis_tarif', 'kode')->ignore($tariffTypeId)->whereNull('deleted_at')
            : Rule::unique('tariff_types', 'code')->ignore($tariffTypeId);
        $componentTable = $isLegacy ? 'legacy.ref_komponen' : 'tariff_components';

        $requiredOrNullable = $code !== null ? 'required' : 'nullable';

        return [
            'name' => 'required|string|max:255',
            'code' => [$requiredOrNullable, 'string', 'max:50', $codeRule],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'components' => 'required|array|min:1',
            'components.*.id' => 'nullable|integer',
            'components.*.tariff_component_id' => ['required', 'integer', "exists:{$componentTable},id"],
            'components.*.percentage' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $components = $this->input('components', []);

            $componentCounts = [];
            foreach ($components as $index => $comp) {
                $compId = $comp['tariff_component_id'] ?? null;
                $pivotId = $comp['id'] ?? null;

                if (! $compId) {
                    continue;
                }

                if (! isset($componentCounts[$compId])) {
                    $componentCounts[$compId] = 0;
                }
                $componentCounts[$compId]++;

                if ($componentCounts[$compId] > 1 && empty($pivotId)) {
                    $validator->errors()->add(
                        "components.{$index}.tariff_component_id",
                        'Komponen tidak boleh duplikat untuk entri baru.'
                    );
                }
            }
        });
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
