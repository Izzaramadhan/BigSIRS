<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyMedicinePackage;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicinePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $package = $this->route('medicine_package');
        $id = $package instanceof \Illuminate\Database\Eloquent\Model ? $package->id : $package;
        $legacy = LegacyMedicinePackage::find($id);

        return $legacy ? ($this->user()?->can('update', $legacy) ?? true) : true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'description' => is_string($this->description) ? trim($this->description) : $this->description,
        ]);
    }
}
