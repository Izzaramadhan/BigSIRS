<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyMedicineUnit;
use App\Models\MasterData\MedicineUnit;
use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicineUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $unit = $this->route('medicine_unit');
        if (LegacyAccess::isAvailable('ref_satuan_obat')) {
            $id = $unit instanceof \Illuminate\Database\Eloquent\Model ? $unit->id : $unit;
            $legacy = LegacyMedicineUnit::find($id);

            return $legacy ? ($this->user()?->can('update', $legacy) ?? true) : true;
        }

        if (is_numeric($unit)) {
            $unit = MedicineUnit::find($unit);
        }

        return $this->user()?->can('update', $unit ?? MedicineUnit::class) ?? true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $unit = $this->route('medicine_unit');
        $id = $unit instanceof \Illuminate\Database\Eloquent\Model ? $unit->id : $unit;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if (! LegacyAccess::isAvailable('ref_satuan_obat')) {
            $rules['name'][] = 'unique:medicine_units,name,' . $id;
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
