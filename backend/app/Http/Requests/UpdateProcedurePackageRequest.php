<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcedurePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'exists:procedure_package_items,id'],
            'items.*.medical_procedure_id' => ['required', 'exists:medical_procedures,id'],
            'items.*.medical_procedure_tariff_id' => ['nullable', 'exists:medical_procedure_tariffs,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'numeric', 'min:0'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ];
    }
}
