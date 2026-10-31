<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockMutationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation (handling camelCase to snake_case).
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('sourceWarehouseId') && ! $this->has('source_warehouse_id')) {
            $merge['source_warehouse_id'] = $this->input('sourceWarehouseId');
        }

        if ($this->has('targetWarehouseId') && ! $this->has('target_warehouse_id')) {
            $merge['target_warehouse_id'] = $this->input('targetWarehouseId');
        }

        if ($this->has('mutationDate') && ! $this->has('mutation_date')) {
            $merge['mutation_date'] = $this->input('mutationDate');
        }

        if ($this->has('items') && is_array($this->input('items'))) {
            $normalizedItems = [];
            foreach ($this->input('items') as $item) {
                $normalizedItems[] = [
                    'medicine_id' => $item['medicine_id'] ?? $item['medicineId'] ?? null,
                    'quantity' => $item['quantity'] ?? null,
                    'batch_number' => $item['batch_number'] ?? $item['batchNumber'] ?? null,
                ];
            }
            $merge['items'] = $normalizedItems;
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'target_warehouse_id' => [
                'required',
                'integer',
                'exists:warehouses,id',
                'different:source_warehouse_id',
            ],
            'mutation_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'integer', 'exists:medicines,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'source_warehouse_id.required' => 'Gudang asal wajib dipilih.',
            'source_warehouse_id.exists' => 'Gudang asal tidak valid.',
            'target_warehouse_id.required' => 'Gudang tujuan wajib dipilih.',
            'target_warehouse_id.exists' => 'Gudang tujuan tidak valid.',
            'target_warehouse_id.different' => 'Gudang tujuan tidak boleh sama dengan gudang asal.',
            'items.required' => 'Minimal satu item obat harus ditambahkan.',
            'items.min' => 'Minimal satu item obat harus ditambahkan.',
            'items.*.medicine_id.required' => 'Obat wajib dipilih.',
            'items.*.medicine_id.exists' => 'Obat tidak ditemukan.',
            'items.*.quantity.required' => 'Kuantitas obat wajib diisi.',
            'items.*.quantity.gt' => 'Kuantitas obat harus lebih besar dari 0.',
        ];
    }
}
