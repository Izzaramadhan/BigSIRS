<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacySupplier;
use App\Models\MasterData\Supplier;
use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $supplier = $this->route('supplier');
        if (LegacyAccess::isAvailable('ref_supplier')) {
            $id = $supplier instanceof \Illuminate\Database\Eloquent\Model ? $supplier->id : $supplier;
            $legacy = LegacySupplier::find($id);

            return $legacy ? ($this->user()?->can('update', $legacy) ?? true) : true;
        }

        if (is_numeric($supplier)) {
            $supplier = Supplier::find($supplier);
        }

        return $this->user()?->can('update', $supplier ?? Supplier::class) ?? true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:15'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'phone' => is_string($this->phone) ? trim($this->phone) : $this->phone,
            'address' => is_string($this->address) ? trim($this->address) : $this->address,
        ]);
    }
}
