<?php

namespace App\Http\Resources\Logistics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sellingPrice = (float) (($this->selling_price !== null && (float) $this->selling_price > 0)
            ? $this->selling_price
            : ($this->medicine?->selling_price_per_unit ?? 0));

        $expDate = $this->expired_date instanceof \DateTimeInterface
            ? $this->expired_date->format('Y-m-d')
            : ($this->expired_date ? (string) $this->expired_date : null);

        $unitName = $this->medicine?->unit?->name ?? 'Unit';

        return [
            // Identifiers
            'id' => $this->id,
            'stock_id' => $this->id,
            'warehouse_stock_id' => $this->id,

            // Item / Medicine details
            'item_id' => $this->medicine_id,
            'medicine_id' => $this->medicine_id,
            'medicineId' => $this->medicine_id,
            'item_code' => $this->medicine?->code,
            'medicine_code' => $this->medicine?->code,
            'medicineCode' => $this->medicine?->code,
            'item_name' => $this->medicine?->name,
            'medicine_name' => $this->medicine?->name,
            'medicineName' => $this->medicine?->name,

            // Unit details
            'unit_id' => $this->medicine?->medicine_unit_id,
            'unit_name' => $unitName,
            'unit' => $unitName,

            // Batch & Expiry
            'batch_number' => $this->batch_number,
            'batchNumber' => $this->batch_number,
            'expiry_date' => $expDate,
            'expired_date' => $expDate,
            'expiredDate' => $expDate,

            // Quantities & Pricing
            'available_stock' => (float) $this->quantity,
            'availableStock' => (float) $this->quantity,
            'selling_price' => $sellingPrice,
            'price' => $sellingPrice,
            'unit_price' => $sellingPrice,
            'unitPrice' => $sellingPrice,
            'formatted_price' => 'Rp'.number_format($sellingPrice, 0, ',', '.'),
            'formattedPrice' => 'Rp'.number_format($sellingPrice, 0, ',', '.'),

            // Warehouse details
            'warehouse_id' => $this->warehouse_id,
            'warehouseId' => $this->warehouse_id,
            'warehouse_name' => $this->warehouse?->name,
            'warehouseName' => $this->warehouse?->name,
        ];
    }
}
