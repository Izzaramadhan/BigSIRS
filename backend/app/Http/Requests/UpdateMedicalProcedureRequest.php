<?php

namespace App\Http\Requests;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isLegacy = LegacyAccess::isAvailable('ref_tarif_tindakan');
        $categoryTable = $isLegacy ? 'legacy.ref_kategori_tindakan' : 'procedure_categories';
        $polyclinicTable = $isLegacy ? 'legacy.ref_poliklinik' : 'polyclinics';
        $reportGroupTable = $isLegacy ? 'legacy.ref_report_group' : 'report_groups';
        $tariffTypeTable = $isLegacy ? 'legacy.ref_jenis_tarif' : 'tariff_types';
        $componentTable = $isLegacy ? 'legacy.ref_komponen' : 'tariff_components';

        return [
            'code' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'procedure_category_id' => ['required', "exists:{$categoryTable},id"],
            'icd9_cm_id' => ['nullable'],
            'is_visible' => ['boolean'],
            'polyclinics' => ['nullable', 'array'],
            'polyclinics.*' => ["exists:{$polyclinicTable},id"],
            'report_groups' => ['nullable', 'array'],
            'report_groups.*' => ["exists:{$reportGroupTable},id"],
            'tariffs' => ['required', 'array', 'min:1'],
            'tariffs.*.id' => ['nullable'],
            'tariffs.*.tariff_type_id' => ['required', "exists:{$tariffTypeTable},id"],
            'tariffs.*.components' => ['required', 'array', 'min:1'],
            'tariffs.*.components.*.id' => ['nullable'],
            'tariffs.*.components.*.tariff_component_id' => ['required', "exists:{$componentTable},id"],
            'tariffs.*.components.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'tariffs.*.components.required' => 'Rincian komponen tarif wajib dilengkapi.',
            'tariffs.*.components.min' => 'Rincian komponen tarif wajib dilengkapi.',
            'tariffs.required' => 'Tindakan harus memiliki setidaknya satu jenis tarif.',
            'tariffs.min' => 'Tindakan harus memiliki setidaknya satu jenis tarif.',
        ];
    }
}
