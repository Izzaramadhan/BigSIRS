<?php

namespace App\Http\Resources\Logistics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosSaleItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $categoryName = $this->medicine?->category?->name ?? 'Obat Bebas';
        $warehouseName = $this->warehouseStock?->warehouse?->name ?? $this->posSale?->warehouse?->name ?? 'Gudang Utama';
        $price = (float) $this->price;
        $subtotal = (float) $this->subtotal;

        return [
            'id' => $this->id,
            'medicineId' => $this->medicine_id,
            'warehouseStockId' => $this->warehouse_stock_id,
            'name' => $this->medicine_name ?? $this->medicine?->name,
            'medicineName' => $this->medicine_name ?? $this->medicine?->name,
            'category' => $categoryName,
            'price' => $price,
            'unitPrice' => $price,
            'formattedPrice' => 'Rp ' . number_format($price, 0, ',', '.'),
            'qty' => (float) $this->quantity,
            'quantity' => (float) $this->quantity,
            'unit' => $this->unit ?? $this->medicine?->unit?->name ?? 'Pcs',
            'subtotal' => $subtotal,
            'formattedSubtotal' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
            'warehouseName' => $warehouseName,
            'warehouseId' => $this->warehouseStock?->warehouse_id ?? $this->posSale?->warehouse_id,
            'batchNumber' => $this->batch_number,
            'expiredDate' => $this->expired_date ? $this->expired_date->format('Y-m-d') : null,
            'notes' => $this->notes,
        ];
    }
}

