<?php

namespace App\Http\Requests;

use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRadiologyGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $catExists = LegacyAccess::isAvailable('ref_grup_rad') ? 'exists:legacy.ref_kategori_rad,id' : 'exists:radiology_categories,id';
        $typeExists = LegacyAccess::isAvailable('ref_grup_rad') ? 'exists:legacy.ref_tipe_rad,id' : 'exists:radiology_types,id';
        $actExists = LegacyAccess::isAvailable('ref_grup_rad') ? 'exists:legacy.ref_jenis_kegiatan,id' : 'exists:activity_types,id';
        $itemGroupExists = LegacyAccess::isAvailable('ref_grup_rad') ? 'exists:legacy.ref_kelompok_item_rad,id' : 'exists:radiology_item_groups,id';

        return [
            'name' => ['required', 'string', 'max:255'],
            'radiology_category_id' => ['required', $catExists],
            'radiology_type_id' => ['required', $typeExists],
            'activity_type_id' => ['required', $actExists],
            'price' => ['required', 'numeric', 'min:0'],
            'interpretation_price' => ['required', 'numeric', 'min:0'],
            'loinc_code' => ['nullable', 'string', 'max:255'],
            'loinc_url' => ['nullable', 'string', 'url', 'max:255'],
            'is_active' => ['boolean'],
            'radiology_item_group_ids' => ['nullable', 'array'],
            'radiology_item_group_ids.*' => [$itemGroupExists, 'distinct'],
        ];
    }
}
