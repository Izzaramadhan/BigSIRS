<?php

namespace App\Http\Resources\Logistics;

use App\Models\Logistics\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WarehouseStock
 */
class StockOpnameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $warehouse = $this->warehouse;
        $medicine = $this->medicine;
        $unit = $medicine?->unit;

        $expDateObj = null;
        if ($this->expired_date) {
            try {
                $expDateObj = Carbon::parse($this->expired_date)->startOfDay();
            } catch (\Throwable) {
                $expDateObj = null;
            }
        }

        $now = Carbon::now()->startOfDay();
        $isExpired = false;
        $isExpiringSoon = false;
        $daysUntilExpiration = null;

        if ($expDateObj) {
            $daysUntilExpiration = (int) $now->diffInDays($expDateObj, false);
            if ($daysUntilExpiration < 0) {
                $isExpired = true;
            } elseif ($daysUntilExpiration <= 90) {
                $isExpiringSoon = true;
            }
        }

        $rawPrice = (float) ($this->selling_price > 0 ? $this->selling_price : ($medicine?->selling_price_per_unit ?? 0));
        $formattedPrice = 'Rp'.number_format($rawPrice, 0, ',', '.');
        $rawStock = (float) $this->quantity;

        $expDateString = $expDateObj ? $expDateObj->format('Y-m-d') : null;
        $expDateDisplay = $expDateObj ? $expDateObj->format('d/m/Y') : '-';

        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'warehouseId' => $this->warehouse_id,
            'warehouse_name' => $warehouse?->name ?? 'Gudang Logistik',
            'warehouse' => [
                'id' => $warehouse?->id ?? $this->warehouse_id,
                'code' => $warehouse?->code ?? '',
                'name' => $warehouse?->name ?? 'Gudang Logistik',
            ],
            'medicine_id' => $this->medicine_id,
            'medicineId' => $this->medicine_id,
            'medicine_code' => $medicine?->code ?? '',
            'medicineCode' => $medicine?->code ?? '',
            'medicine_name' => $medicine?->name ?? 'Sediaan',
            'medicineName' => $medicine?->name ?? 'Sediaan',
            'medicine' => [
                'id' => $medicine?->id ?? $this->medicine_id,
                'code' => $medicine?->code ?? '',
                'name' => $medicine?->name ?? 'Sediaan',
            ],
            'unit_id' => $unit?->id,
            'unit_name' => $unit?->name ?? 'Unit',
            'unit' => [
                'id' => $unit?->id ?? 1,
                'name' => $unit?->name ?? 'Unit',
            ],
            'batch_number' => $this->batch_number ?: '-',
            'batchNumber' => $this->batch_number ?: '-',
            'expiration_date' => $expDateString,
            'expirationDate' => $expDateString,
            'expiration_date_display' => $expDateDisplay,
            'expirationDateDisplay' => $expDateDisplay,
            'selling_price' => $rawPrice,
            'sellingPrice' => $rawPrice,
            'formatted_selling_price' => $formattedPrice,
            'formattedSellingPrice' => $formattedPrice,
            'stock' => $rawStock,
            'quantity' => $rawStock,
            'is_expired' => $isExpired,
            'isExpired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'isExpiringSoon' => $isExpiringSoon,
            'days_until_expiration' => $daysUntilExpiration,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
