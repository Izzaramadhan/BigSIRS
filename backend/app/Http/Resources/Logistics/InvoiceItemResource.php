<?php

namespace App\Http\Resources\Logistics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medicineId' => $this->medicine_id,
            'medicine_id' => $this->medicine_id,
            'medicineName' => $this->medicine_name ?? $this->medicine?->name,
            'medicine_name' => $this->medicine_name ?? $this->medicine?->name,
            'medicineCode' => $this->medicine?->code,
            'medicine_code' => $this->medicine?->code,
            'quantity' => (float) $this->quantity,
            'unit' => $this->unit,
            'price' => (float) $this->price,
            'unitPrice' => (float) $this->price,
            'discountType' => $this->discount_type ?? 'percentage',
            'discount_type' => $this->discount_type ?? 'percentage',
            'discount' => (float) $this->discount,
            'subtotal' => (float) $this->subtotal,
            'isRepack' => (bool) $this->is_repack,
            'is_repack' => (bool) $this->is_repack,
            'margin' => (float) $this->margin,
            'batchNumber' => $this->batch_number,
            'batch_number' => $this->batch_number,
            'expiredDate' => $this->expired_date?->format('Y-m-d') ?? $this->expired_date,
            'expired_date' => $this->expired_date?->format('Y-m-d') ?? $this->expired_date,
            'notes' => $this->notes,
        ];
    }
}
