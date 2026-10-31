<?php

namespace App\Http\Resources\Logistics;

use App\Models\Logistics\StockOpnameItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockOpnameItem
 */
class StockOpnameItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $systemStock = (float) $this->system_stock;
        $physicalStock = $this->physical_stock !== null ? (float) $this->physical_stock : null;
        $difference = $this->difference !== null ? (float) $this->difference : null;
        $unitPrice = (float) $this->unit_price;
        $diffValue = $this->difference_value !== null ? (float) $this->difference_value : null;

        $diffStatusLabel = 'Belum Dihitung';
        if ($difference !== null) {
            if ($difference === 0.0) {
                $diffStatusLabel = 'Match';
            } elseif ($difference < 0) {
                $diffStatusLabel = 'Kurang';
            } else {
                $diffStatusLabel = 'Lebih';
            }
        }

        return [
            'id' => $this->id,
            'stock_opname_id' => $this->stock_opname_id,
            'stockOpnameId' => $this->stock_opname_id,
            'warehouse_stock_id' => $this->warehouse_stock_id,
            'medicine_id' => $this->medicine_id,
            'medicineId' => $this->medicine_id,
            'name' => $this->medicine_name,
            'medicine_name' => $this->medicine_name,
            'medicineName' => $this->medicine_name,
            'code' => $this->medicine_code,
            'medicine_code' => $this->medicine_code,
            'medicineCode' => $this->medicine_code,
            'unit' => $this->unit,
            'batch_number' => $this->batch_number,
            'batchNumber' => $this->batch_number,
            'expired_date' => $this->expired_date?->format('Y-m-d'),
            'expiredDate' => $this->expired_date?->format('Y-m-d'),
            'system_stock' => $systemStock,
            'systemStock' => $systemStock,
            'physical_stock' => $physicalStock,
            'physicalStock' => $physicalStock,
            'difference' => $difference,
            'unit_price' => $unitPrice,
            'unitPrice' => $unitPrice,
            'difference_value' => $diffValue,
            'differenceValue' => $diffValue,
            'formatted_unit_price' => 'Rp'.number_format($unitPrice, 0, ',', '.'),
            'formattedUnitPrice' => 'Rp'.number_format($unitPrice, 0, ',', '.'),
            'formatted_difference_value' => $diffValue !== null ? 'Rp'.number_format(abs($diffValue), 0, ',', '.') : '-',
            'formattedDifferenceValue' => $diffValue !== null ? 'Rp'.number_format(abs($diffValue), 0, ',', '.') : '-',
            'difference_status_label' => $diffStatusLabel,
            'notes' => $this->notes,
            'counted_by' => $this->counted_by,
            'counted_at' => $this->counted_at?->toIso8601String(),
        ];
    }
}
