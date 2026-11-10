<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyMedicine;
use App\Models\MasterData\Medicine;
use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $medicine = $this->route('medicine');
        if (LegacyAccess::isAvailable('ref_obat')) {
            $id = $medicine instanceof \Illuminate\Database\Eloquent\Model ? $medicine->id : $medicine;
            $legacy = LegacyMedicine::find($id);

            return $legacy ? ($this->user()?->can('update', $legacy) ?? true) : true;
        }

        if (is_numeric($medicine)) {
            $medicine = Medicine::find($medicine);
        }

        return $this->user()?->can('update', $medicine ?? Medicine::class) ?? true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $medicine = $this->route('medicine');
        $id = $medicine instanceof \Illuminate\Database\Eloquent\Model ? $medicine->id : $medicine;

        $rules = [
            'code' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'kfa_code' => ['nullable', 'string', 'max:255'],
            'bpjs_code' => ['nullable', 'string', 'max:50'],
            'function' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'selling_price_per_unit' => ['nullable', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'medicine_unit_id' => ['nullable'],
            'medicine_category_id' => ['nullable'],
            'medicine_classification_id' => ['nullable'],
            'medicine_route_id' => ['nullable'],
            'generic_medicine_id' => ['nullable'],
        ];

        if (! LegacyAccess::isAvailable('ref_obat')) {
            $rules['code'][] = 'unique:medicines,code,' . $id;
            $rules['medicine_unit_id'][] = 'exists:medicine_units,id';
            $rules['medicine_category_id'][] = 'exists:medicine_categories,id';
            $rules['medicine_classification_id'][] = 'exists:medicine_classifications,id';
            $rules['medicine_route_id'][] = 'exists:medicine_routes,id';
            $rules['generic_medicine_id'][] = 'exists:generic_medicines,id';
        } else {
            $rules['medicine_unit_id'][] = 'integer';
            $rules['medicine_category_id'][] = 'integer';
            $rules['medicine_classification_id'][] = 'integer';
            $rules['medicine_route_id'][] = 'integer';
            $rules['generic_medicine_id'][] = 'integer';
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? trim($this->code) : $this->code,
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'kfa_code' => is_string($this->kfa_code) ? trim($this->kfa_code) : $this->kfa_code,
            'bpjs_code' => is_string($this->bpjs_code) ? trim($this->bpjs_code) : $this->bpjs_code,
            'function' => is_string($this->function) ? trim($this->function) : $this->function,
            'description' => is_string($this->description) ? trim($this->description) : $this->description,
        ]);
    }
}

