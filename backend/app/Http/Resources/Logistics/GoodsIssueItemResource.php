<?php

namespace App\Http\Resources\Logistics;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsIssueItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $expDate = null;
        if ($this->expired_date) {
            $expDate = $this->expired_date instanceof Carbon
                ? $this->expired_date->format('Y-m-d')
                : Carbon::parse($this->expired_date)->format('Y-m-d');
        }

        $availableStock = $this->warehouseStock
            ? (float) $this->warehouseStock->quantity
            : (float) $this->quantity;

        $warehouse = $this->warehouseStock?->warehouse ?? $this->goodsIssue?->warehouse;
        $warehouseName = $warehouse?->name ?? 'Gudang Utama';

        return [
            'id' => $this->id,
            'goodsIssueId' => $this->goods_issue_id,
            'goods_issue_id' => $this->goods_issue_id,
            'warehouseStockId' => $this->warehouse_stock_id,
            'warehouse_stock_id' => $this->warehouse_stock_id,
            'warehouseId' => $warehouse?->id,
            'warehouse_id' => $warehouse?->id,
            'warehouseName' => $warehouseName,
            'warehouse_name' => $warehouseName,
            'medicineId' => $this->medicine_id,
            'medicine_id' => $this->medicine_id,
            'name' => $this->medicine_name,
            'medicineName' => $this->medicine_name,
            'medicine_name' => $this->medicine_name,
            'medicineCode' => $this->medicine?->code,
            'batchNumber' => $this->batch_number,
            'batch_number' => $this->batch_number,
            'expDate' => $expDate,
            'expiredDate' => $expDate,
            'expired_date' => $expDate,
            'availableStock' => $availableStock,
            'available_stock' => $availableStock,
            'quantity' => (float) $this->quantity,
            'unit' => $this->unit ?? $this->medicine?->unit?->name ?? 'Unit',
            'unitPrice' => (float) $this->unit_price,
            'unit_price' => (float) $this->unit_price,
            'formattedPrice' => 'Rp '.number_format((float) $this->unit_price, 0, ',', '.'),
            'subtotal' => (float) $this->subtotal,
            'formattedSubtotal' => 'Rp '.number_format((float) $this->subtotal, 0, ',', '.'),
            'notes' => $this->notes,
        ];
    }
}

