<?php

namespace App\Http\Resources\Logistics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMutationItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $unitPrice = (float) ($this->unit_price ?? $this->medicine?->selling_price_per_unit ?? 0);
        $subtotal = (float) ($this->subtotal ?? ((float) $this->quantity * $unitPrice));

        return [
            'id' => $this->id,
            'medicineId' => $this->medicine_id,
            'medicineName' => $this->medicine_name,
            'quantity' => (float) $this->quantity,
            'unit' => $this->unit ?? 'Unit',
            'unitPrice' => $unitPrice,
            'subtotal' => $subtotal,
            'formattedUnitPrice' => 'Rp'.number_format($unitPrice, 0, ',', '.'),
            'formattedSubtotal' => 'Rp'.number_format($subtotal, 0, ',', '.'),
            'batchNumber' => $this->batch_number,
            'expiredDate' => $this->expired_date?->format('Y-m-d') ?? $this->expired_date,
        ];
    }
}
