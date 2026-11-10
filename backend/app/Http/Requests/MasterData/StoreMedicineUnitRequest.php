<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyMedicineUnit;
use App\Models\MasterData\MedicineUnit;
use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicineUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $authModel = LegacyAccess::isAvailable('ref_satuan_obat') ? LegacyMedicineUnit::class : MedicineUnit::class;

        return $this->user()?->can('create', $authModel) ?? true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if (! LegacyAccess::isAvailable('ref_satuan_obat')) {
            $rules['name'][] = 'unique:medicine_units,name';
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'description' => is_string($this->description) ? trim($this->description) : $this->description,
        ]);
    }
}
