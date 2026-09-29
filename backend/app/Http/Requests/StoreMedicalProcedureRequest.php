<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'procedure_category_id' => ['required', 'exists:procedure_categories,id'],
            'icd9_cm_id' => ['nullable', 'exists:icd9_cms,id'],
            'is_visible' => ['boolean'],
            'polyclinics' => ['nullable', 'array'],
            'polyclinics.*' => ['exists:polyclinics,id'],
            'report_groups' => ['nullable', 'array'],
            'report_groups.*' => ['exists:report_groups,id'],
            'tariffs' => ['required', 'array', 'min:1'],
            'tariffs.*.tariff_type_id' => ['required', 'exists:tariff_types,id'],
            'tariffs.*.components' => ['required', 'array', 'min:1'],
            'tariffs.*.components.*.tariff_component_id' => ['required', 'exists:tariff_components,id'],
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
