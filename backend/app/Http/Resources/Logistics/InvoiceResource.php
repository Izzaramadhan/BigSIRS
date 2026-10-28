<?php

namespace App\Http\Resources\Logistics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $supplierName = $this->supplier_name ?: ($this->supplier?->name ?? 'Supplier');
        $supplierCity = $this->supplier_city ?: ($this->supplier?->address ?? '');

        return [
            'id' => $this->id,
            'legacy_id' => $this->legacy_id,
            'invoiceNumber' => $this->invoice_number,
            'invoice_number' => $this->invoice_number,
            'referenceNumber' => $this->reference_number,
            'reference_number' => $this->reference_number,
            'invoiceDate' => $this->invoice_date?->format('Y-m-d') ?? $this->invoice_date,
            'invoice_date' => $this->invoice_date?->format('Y-m-d') ?? $this->invoice_date,
            'dueDate' => $this->due_date?->format('Y-m-d') ?? $this->due_date,
            'due_date' => $this->due_date?->format('Y-m-d') ?? $this->due_date,
            'supplierId' => $this->supplier_id,
            'supplier_id' => $this->supplier_id,
            'supplierName' => $supplierName,
            'supplier_name' => $supplierName,
            'supplierCity' => $supplierCity,
            'supplier_city' => $supplierCity,
            'supplierPhone' => $this->supplier?->phone ?? '',
            'supplier_phone' => $this->supplier?->phone ?? '',
            'supplierNpwp' => $this->supplier_npwp ?: ($this->supplier?->phone ?? ''),
            'supplier_npwp' => $this->supplier_npwp ?: ($this->supplier?->phone ?? ''),
            'warehouseId' => $this->warehouse_id,
            'warehouse_id' => $this->warehouse_id,
            'warehouseName' => $this->warehouse?->name ?? 'Gudang Utama',
            'warehouse_name' => $this->warehouse?->name ?? 'Gudang Utama',
            'status' => $this->status,
            'taxType' => $this->tax_type ?? 'non_tax',
            'tax_type' => $this->tax_type ?? 'non_tax',
            'taxRate' => (float) ($this->tax_rate ?? 0),
            'tax_rate' => (float) ($this->tax_rate ?? 0),
            'notes' => $this->notes,
            'paidAt' => $this->paid_at?->format('Y-m-d') ?? $this->paid_at,
            'paid_at' => $this->paid_at?->format('Y-m-d') ?? $this->paid_at,
            'subtotalAmount' => (float) $this->subtotal_amount,
            'subtotal_amount' => (float) $this->subtotal_amount,
            'discountAmount' => (float) $this->discount_amount,
            'discount_amount' => (float) $this->discount_amount,
            'taxAmount' => (float) $this->tax_amount,
            'tax_amount' => (float) $this->tax_amount,
            'totalAmount' => (float) $this->total_amount,
            'total_amount' => (float) $this->total_amount,
            'paidAmount' => (float) $this->paid_amount,
            'paid_amount' => (float) $this->paid_amount,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'supplier' => $this->whenLoaded('supplier'),
            'warehouse' => $this->whenLoaded('warehouse'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
