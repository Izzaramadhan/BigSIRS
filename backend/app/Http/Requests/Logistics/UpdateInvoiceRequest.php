<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawStatus = $this->input('status', $this->input('payment_status'));
        $status = null;
        if ($rawStatus !== null) {
            $status = in_array(strtolower((string) $rawStatus), ['lunas', 'paid'], true) ? 'Lunas' : 'Belum Lunas';
        }

        $dueDate = $this->input('due_date', $this->input('dueDate'));
        if ($status === 'Lunas') {
            $dueDate = null;
        }

        $this->merge([
            'invoice_number' => $this->input('invoice_number', $this->input('invoiceNumber')),
            'reference_number' => $this->input('reference_number', $this->input('referenceNumber')),
            'invoice_date' => $this->input('invoice_date', $this->input('invoiceDate')),
            'due_date' => $dueDate,
            'supplier_id' => $this->input('supplier_id', $this->input('supplierId')),
            'warehouse_id' => $this->input('warehouse_id', $this->input('warehouseId')),
            'status' => $status ?? $rawStatus,
            'tax_type' => $this->input('tax_type', $this->input('taxType', 'non_tax')),
            'tax_rate' => $this->input('tax_rate', $this->input('taxRate', 0)),
            'total_amount' => $this->input('total_amount', $this->input('totalAmount')),
        ]);
    }

    public function rules(): array
    {
        $invoiceId = $this->route('invoice');
        if (is_object($invoiceId)) {
            $invoice = $invoiceId;
            $invoiceId = $invoice->id;
        } else {
            $invoice = null;
        }

        $rawStatus = $this->input('status', $this->input('payment_status'));
        if ($rawStatus === null && $invoice) {
            $rawStatus = $invoice->status;
        }
        $isPaid = in_array(strtolower((string) ($rawStatus ?? 'Belum Lunas')), ['lunas', 'paid'], true);

        return [
            'invoice_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('invoices', 'invoice_number')->ignore($invoiceId),
            ],
            'reference_number' => 'nullable|string|max:100',
            'invoice_date' => 'required|date',
            'due_date' => [
                'nullable',
                Rule::requiredIf(! $isPaid),
                'date',
                'after_or_equal:invoice_date',
            ],
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'status' => 'nullable|string|in:Belum Lunas,Lunas,unpaid,paid',
            'tax_type' => 'nullable|string|in:non_tax,ppn,ppn_11,tax',
            'tax_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.medicineId' => 'nullable|integer|exists:medicines,id',
            'items.*.medicine_id' => 'nullable|integer|exists:medicines,id',
            'items.*.item_id' => 'nullable|integer|exists:medicines,id',
            'items.*.medicineName' => 'nullable|string|max:255',
            'items.*.medicine_name' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit' => 'nullable|string|max:100',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.unitPrice' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|string|in:percentage,nominal',
            'items.*.discountType' => 'nullable|string|in:percentage,nominal',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.is_repack' => 'nullable|boolean',
            'items.*.isRepack' => 'nullable|boolean',
            'items.*.margin' => 'nullable|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.batchNumber' => 'nullable|string|max:100',
            'items.*.expired_date' => 'nullable|date',
            'items.*.expiredDate' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_number.required' => 'Nomor faktur wajib diisi.',
            'invoice_number.unique' => 'Nomor faktur sudah pernah digunakan.',
            'invoice_date.required' => 'Tanggal faktur wajib diisi.',
            'due_date.required' => 'Tanggal jatuh tempo wajib diisi.',
            'due_date.required_if' => 'Tanggal jatuh tempo wajib diisi.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo tidak boleh lebih awal dari tanggal faktur.',
            'supplier_id.required' => 'Supplier wajib dipilih.',
            'supplier_id.exists' => 'Supplier yang dipilih tidak valid.',
            'warehouse_id.exists' => 'Gudang yang dipilih tidak valid.',
            'items.required' => 'Faktur harus memiliki minimal satu detail obat.',
            'items.min' => 'Faktur harus memiliki minimal satu detail obat.',
            'items.*.quantity.required' => 'Kuantitas obat wajib diisi.',
            'items.*.quantity.gt' => 'Kuantitas obat harus lebih besar dari 0.',
            'items.*.price.min' => 'Harga beli obat tidak boleh bernilai negatif.',
            'items.*.unit_price.min' => 'Harga beli obat tidak boleh bernilai negatif.',
            'items.*.discount.min' => 'Diskon tidak boleh bernilai negatif.',
            'items.*.margin.min' => 'Margin tidak boleh bernilai negatif.',
        ];
    }
}
