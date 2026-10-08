<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRadiologyGroupRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'radiology_category_id' => ['required', 'exists:radiology_categories,id'],
            'radiology_type_id' => ['required', 'exists:radiology_types,id'],
            'activity_type_id' => ['required', 'exists:activity_types,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'interpretation_price' => ['required', 'numeric', 'min:0'],
            'loinc_code' => ['nullable', 'string', 'max:255'],
            'loinc_url' => ['nullable', 'string', 'url', 'max:255'],
            'is_active' => ['boolean'],
            'radiology_item_group_ids' => ['nullable', 'array'],
            'radiology_item_group_ids.*' => ['exists:radiology_item_groups,id', 'distinct'],
        ];
    }
}
