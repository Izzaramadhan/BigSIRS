<?php

namespace App\Http\Requests;

use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRadiologyItemRequest extends FormRequest
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
        $groupExists = LegacyAccess::isAvailable('ref_item_rad')
            ? 'exists:legacy.ref_kelompok_item_rad,id'
            : 'exists:radiology_item_groups,id';

        return [
            'name' => ['required', 'string', 'max:255'],
            'radiology_item_group_id' => ['required', 'integer', $groupExists],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
