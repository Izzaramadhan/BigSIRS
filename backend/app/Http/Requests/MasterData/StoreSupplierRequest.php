<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacySupplier;
use App\Models\MasterData\Supplier;
use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $authModel = LegacyAccess::isAvailable('ref_supplier') ? LegacySupplier::class : Supplier::class;

        return $this->user()?->can('create', $authModel) ?? true;
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
