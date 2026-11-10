<?php

namespace App\Http\Requests;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProcedurePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isLegacy = LegacyAccess::isAvailable('ref_paket_tindakan');
        $itemTable = $isLegacy ? 'legacy.map_paket_tindakan' : 'procedure_package_items';
        $procedureTable = $isLegacy ? 'legacy.ref_tarif_tindakan' : 'medical_procedures';

        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', "exists:{$itemTable},id"],
            'items.*.medical_procedure_id' => ['required', "exists:{$procedureTable},id"],
            'items.*.medical_procedure_tariff_id' => ['nullable'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'numeric', 'min:0'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ];
    }
}

