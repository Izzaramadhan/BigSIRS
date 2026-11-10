<?php

namespace App\Http\Requests\MasterData;

use App\Models\Legacy\LegacyWarehouse;
use App\Models\MasterData\Warehouse;
use App\Support\LegacyAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $warehouse = $this->route('warehouse');
        if (LegacyAccess::isAvailable('ref_gudang')) {
            $id = $warehouse instanceof \Illuminate\Database\Eloquent\Model ? $warehouse->id : $warehouse;
            $legacy = LegacyWarehouse::find($id);

            return $legacy ? ($this->user()?->can('update', $legacy) ?? true) : true;
        }

        if (is_numeric($warehouse)) {
            $warehouse = Warehouse::find($warehouse);
        }

        return $this->user()?->can('update', $warehouse ?? Warehouse::class) ?? true;
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
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'code' => is_string($this->code) ? trim($this->code) : $this->code,
            'description' => is_string($this->description) ? trim($this->description) : $this->description,
        ]);
    }
}
