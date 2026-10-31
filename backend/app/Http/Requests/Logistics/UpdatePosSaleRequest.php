<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $isDraft = filter_var($this->input('is_draft', $this->input('isDraft', true)), FILTER_VALIDATE_BOOLEAN);

        $data = [
            'is_draft' => $isDraft,
            'customer_id' => $this->input('customer_id', $this->input('customerId')),
            'customer_name' => $this->input('customer_name', $this->input('customerName', $this->input('pembeli'))),
            'customer_type' => $this->input('customer_type', $this->input('customerType', 'Umum')),
            'transaction_date' => $this->input('transaction_date', $this->input('transactionDate', $this->input('date'))),
            'payment_method' => $this->input('payment_method', $this->input('paymentMethod', 'Tunai')),
            'paid_amount' => $this->input('paid_amount', $this->input('paidAmount', 0)),
            'location' => $this->input('location', $this->input('warehouse_id')),
            'notes' => $this->input('notes'),
        ];

        if ($this->has('items') && is_array($this->input('items'))) {
            $normalizedItems = [];
            foreach ($this->input('items') as $it) {
                $qty = (float) ($it['quantity'] ?? $it['qty'] ?? 1);
                $price = (float) ($it['price'] ?? 0);
                $subtotal = ! empty($it['subtotal']) ? (float) $it['subtotal'] : ($qty * $price);

                $normalizedItems[] = [
                    'medicine_id' => $it['medicine_id'] ?? $it['medicineId'] ?? $it['id'] ?? null,
                    'warehouse_stock_id' => $it['warehouse_stock_id'] ?? $it['warehouseStockId'] ?? null,
                    'quantity' => $qty,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'batch_number' => $it['batch_number'] ?? $it['batchNumber'] ?? null,
                    'expired_date' => $it['expired_date'] ?? $it['expiredDate'] ?? null,
                    'unit' => $it['unit'] ?? null,
                    'name' => $it['name'] ?? $it['medicine_name'] ?? null,
                    'notes' => $it['notes'] ?? null,
                ];
            }
            $data['items'] = $normalizedItems;
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $isDraft = $this->boolean('is_draft');

        return [
            'is_draft' => ['nullable', 'boolean'],
            'customer_id' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', 'string', 'max:50'],
            'warehouse_id' => ['nullable', 'integer'],
            'location' => ['nullable'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'transaction_date' => ['nullable', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.medicine_id' => ['required_with:items', 'integer'],
            'items.*.warehouse_stock_id' => ['nullable', 'integer'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:100'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.subtotal' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
