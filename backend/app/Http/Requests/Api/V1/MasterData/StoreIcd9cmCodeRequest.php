<?php

namespace App\Http\Requests\Api\V1\MasterData;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreIcd9cmCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isLegacy = LegacyAccess::isAvailable('ref_icd_9') || LegacyAccess::isAvailable('ref_icd9');
        $table = $isLegacy
            ? 'legacy.' . (LegacyAccess::isAvailable('ref_icd_9') ? 'ref_icd_9' : 'ref_icd9')
            : 'icd9_cms';
        $column = $isLegacy ? 'kode' : 'code';

        return [
            'code' => ['required', 'string', 'max:50', "unique:{$table},{$column}"],
            'name' => 'required|string|max:255',
            'english_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'inacbg_code' => 'nullable|string|max:100',
            'inacbg_name' => 'nullable|string',
            'class_1_tariff' => 'nullable|numeric|min:0',
            'class_2_tariff' => 'nullable|numeric|min:0',
            'class_3_tariff' => 'nullable|numeric|min:0',
        ];
    }
}
