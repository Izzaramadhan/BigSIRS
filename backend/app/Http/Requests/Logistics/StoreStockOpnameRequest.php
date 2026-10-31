<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $warehouseId = $this->input('warehouse_id') ?? $this->input('warehouseId') ?? $this->input('warehouse');
        if ($warehouseId !== null && is_numeric($warehouseId)) {
            $this->merge(['warehouse_id' => (int) $warehouseId]);
        }

        if ($this->has('officerName') && ! $this->has('officer_name')) {
            $this->merge(['officer_name' => $this->input('officerName')]);
        }

        if ($this->has('officerRole') && ! $this->has('officer_role')) {
            $this->merge(['officer_role' => $this->input('officerRole')]);
        }

        if ($this->has('locationDetail') && ! $this->has('location_detail')) {
            $this->merge(['location_detail' => $this->input('locationDetail')]);
        }

        if ($this->has('startDateTime') && ! $this->has('start_date')) {
            $this->merge(['start_date' => $this->input('startDateTime')]);
        }

        if ($this->has('startImmediately') && ! $this->has('start_immediately')) {
            $this->merge(['start_immediately' => filter_var($this->input('startImmediately'), FILTER_VALIDATE_BOOLEAN)]);
        }

        if ($this->has('isDraft') && ! $this->has('is_draft')) {
            $this->merge(['is_draft' => filter_var($this->input('isDraft'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'location_detail' => ['nullable', 'string', 'max:255'],
            'officer_name' => ['required', 'string', 'max:255'],
            'officer_role' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'start_immediately' => ['nullable', 'boolean'],
            'is_draft' => ['nullable', 'boolean'],
            'isDraft' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Gudang atau lokasi audit persediaan wajib dipilih.',
            'warehouse_id.exists' => 'Gudang yang dipilih tidak ditemukan dalam sistem.',
            'officer_name.required' => 'Petugas atau penanggung jawab audit wajib diisi.',
        ];
    }
}
