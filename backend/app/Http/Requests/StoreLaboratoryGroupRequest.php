<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLaboratoryGroupRequest extends FormRequest
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
            'laboratory_category_id' => ['required', 'integer', 'exists:laboratory_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'harga_jaspel' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'loinc_code' => ['nullable', 'string', 'max:100'],
            'loinc_url' => ['nullable', 'url', 'max:255'],
            'snomed_code' => ['nullable', 'string', 'max:100'],
            'snomed_url' => ['nullable', 'url', 'max:255'],
            'laboratory_item_ids' => ['required', 'array', 'min:1'],
            'laboratory_item_ids.*' => ['required', 'integer', 'distinct', 'exists:laboratory_items,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (is_string($this->name)) {
            $merge['name'] = trim(preg_replace('/\s+/', ' ', $this->name));
        }

        if ($this->has('laboratory_item_id') && ! $this->has('laboratory_item_ids')) {
            $itemId = $this->input('laboratory_item_id');
            $merge['laboratory_item_ids'] = $itemId ? [(int) $itemId] : [];
        } elseif ($this->has('item_id') && ! $this->has('laboratory_item_ids')) {
            $itemId = $this->input('item_id');
            $merge['laboratory_item_ids'] = $itemId ? [(int) $itemId] : [];
        } elseif ($this->has('items') && ! $this->has('laboratory_item_ids')) {
            $items = $this->input('items');
            $merge['laboratory_item_ids'] = is_array($items) ? $items : ($items ? [(int) $items] : []);
        }

        foreach (['loinc_code', 'loinc_url', 'snomed_code', 'snomed_url', 'description'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $val = trim($this->input($field));
                $merge[$field] = $val === '' ? null : $val;
            }
        }

        foreach (['price', 'harga_jaspel'] as $field) {
            if ($this->has($field) && ($this->input($field) === '' || $this->input($field) === null)) {
                $merge[$field] = null;
            }
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }
}
