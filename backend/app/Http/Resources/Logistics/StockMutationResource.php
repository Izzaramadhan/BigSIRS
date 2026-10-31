<?php

namespace App\Http\Resources\Logistics;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMutationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $creatorName = $this->creator?->name ?? 'Administrator';
        $words = array_values(array_filter(explode(' ', trim($creatorName))));
        if (count($words) >= 2) {
            $cleanWords = array_values(array_filter($words, fn ($w) => ! in_array(strtolower(rtrim($w, '.')), ['apt', 'dr', 'ns', 'prof'])));
            if (count($cleanWords) >= 2) {
                $initials = strtoupper(substr($cleanWords[0], 0, 1).substr($cleanWords[1], 0, 1));
            } else {
                $initials = strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
            }
        } elseif (count($words) === 1) {
            $initials = strtoupper(substr($words[0], 0, min(2, strlen($words[0]))));
        } else {
            $initials = 'AD';
        }

        $date = $this->mutation_date ? Carbon::parse($this->mutation_date) : null;
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $dateDisplay = $date
            ? $date->format('d').' '.$months[(int) $date->format('n')].' '.$date->format('Y, H:i').' WIB'
            : '-';

        $totalQuantity = $this->relationLoaded('items')
            ? (float) $this->items->sum('quantity')
            : (float) ($this->items_sum_quantity ?? 0);

        $itemCount = $this->relationLoaded('items')
            ? $this->items->count()
            : (int) ($this->items_count ?? 0);

        $primaryUnit = $this->relationLoaded('items') && $this->items->isNotEmpty()
            ? $this->items->first()->unit ?? 'Unit'
            : 'Unit';

        $totalValue = (float) ($this->total_value ?? ($this->relationLoaded('items') ? $this->items->sum('subtotal') : 0));

        return [
            'id' => $this->id,
            'mutationNumber' => $this->mutation_number,
            'mutation_number' => $this->mutation_number,
            'mutationDate' => $date?->toIso8601String(),
            'mutation_date' => $date?->toIso8601String(),
            'dateDisplay' => $dateDisplay,
            'sourceWarehouseId' => $this->source_warehouse_id,
            'source_warehouse_id' => $this->source_warehouse_id,
            'sourceWarehouse' => $this->sourceWarehouse?->name ?? 'Gudang Asal',
            'source_warehouse' => $this->sourceWarehouse?->name ?? 'Gudang Asal',
            'targetWarehouseId' => $this->target_warehouse_id,
            'target_warehouse_id' => $this->target_warehouse_id,
            'targetWarehouse' => $this->targetWarehouse?->name ?? 'Gudang Tujuan',
            'target_warehouse' => $this->targetWarehouse?->name ?? 'Gudang Tujuan',
            'itemCount' => $itemCount,
            'totalQuantity' => $totalQuantity,
            'totalValue' => $totalValue,
            'formattedTotalValue' => 'Rp'.number_format($totalValue, 0, ',', '.'),
            'unit' => $primaryUnit,
            'notes' => $this->notes ?? '-',
            'status' => $this->status,
            'createdBy' => $creatorName,
            'createdInitials' => $initials,
            'validatedAt' => $this->validated_at ? Carbon::parse($this->validated_at)->toIso8601String() : null,
            'items' => StockMutationItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
