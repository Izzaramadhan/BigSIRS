<?php

namespace App\Http\Requests;

use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRadiologyTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorized via controller/middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $uniqueRule = LegacyAccess::isAvailable('ref_tipe_rad')
            ? Rule::unique('legacy.ref_tipe_rad', 'nama')->whereNull('deleted_at')
            : Rule::unique('radiology_types', 'name')->whereNull('deleted_at');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueRule,
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (is_string($this->name)) {
            $merge['name'] = trim(preg_replace('/\s+/', ' ', $this->name));
        }

        if ($this->has('description') && is_string($this->input('description'))) {
            $val = trim($this->input('description'));
            $merge['description'] = $val === '' ? null : $val;
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }
}
