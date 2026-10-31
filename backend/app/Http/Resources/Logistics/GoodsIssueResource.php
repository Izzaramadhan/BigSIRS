<?php

namespace App\Http\Resources\Logistics;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsIssueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $date = $this->issue_date ? Carbon::parse($this->issue_date) : null;
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $dateDisplay = $date
            ? $date->format('d').' '.$months[(int) $date->format('n')].' '.$date->format('Y')
            : '-';

        $formattedDate = $date ? $date->format('d-m-Y') : '-';

        $timeDisplay = $date
            ? $date->format('H:i').' WIB'
            : '-';

        $dateTime = $date ? $date->format('Y-m-d H:i') : '';

        $totalQuantity = $this->relationLoaded('items')
            ? (float) $this->items->sum('quantity')
            : (float) ($this->total_quantity ?? 0);

        $itemCount = $this->relationLoaded('items')
            ? $this->items->count()
            : (int) ($this->items_count ?? 0);

        $primaryUnit = $this->relationLoaded('items') && $this->items->isNotEmpty()
            ? $this->items->first()->unit ?? 'Unit'
            : 'Unit';

        // Map status to standard display values: 'Draft', 'Selesai', 'Dibatalkan'
        $statusKey = strtolower($this->status ?? 'draft');
        $statusDisplay = match ($statusKey) {
            'completed', 'selesai' => 'Selesai',
            'cancelled', 'dibatalkan' => 'Dibatalkan',
            default => 'Draft',
        };

        return [
            'id' => $this->id,
            'issueNumber' => $this->issue_number,
            'issue_number' => $this->issue_number,
            'referenceNumber' => $this->reference_number,
            'reference_number' => $this->reference_number,
            'dateDisplay' => $dateDisplay,
            'formattedDate' => $formattedDate,
            'formatted_date' => $formattedDate,
            'timeDisplay' => $timeDisplay,
            'dateTime' => $dateTime,
            'issueDate' => $date?->toIso8601String(),
            'issue_date' => $dateTime,
            'warehouse' => $this->warehouse?->name ?? 'Gudang Utama Farmasi',
            'warehouseId' => $this->warehouse_id,
            'warehouse_id' => $this->warehouse_id,
            'locationDetail' => $this->location_detail ?? 'Rak Distribusi',
            'location_detail' => $this->location_detail,
            'officerName' => $this->officer_name,
            'officer_name' => $this->officer_name,
            'officerId' => $this->officer_id,
            'officer_id' => $this->officer_id,
            'officerRole' => is_string($this->officer_role) && ! str_starts_with($this->officer_role, '{')
                ? $this->officer_role
                : ($this->officer?->position?->name ?? 'Petugas Logistik Farmasi'),
            'officer_role' => is_string($this->officer_role) && ! str_starts_with($this->officer_role, '{')
                ? $this->officer_role
                : ($this->officer?->position?->name ?? 'Petugas Logistik Farmasi'),
            'purpose' => $this->notes !== null && $this->notes !== '' ? $this->notes : ($this->purpose ?: '-'),
            'purposeKey' => $this->purpose_code ?? $this->purpose,
            'purpose_code' => $this->purpose_code,
            'itemCount' => $itemCount,
            'item_count' => $itemCount,
            'totalQuantity' => $totalQuantity,
            'total_quantity' => $totalQuantity,
            'quantityUnit' => $primaryUnit,
            'quantitySubtitle' => "{$itemCount} Jenis Barang",
            'totalValue' => (float) ($this->total_value ?? 0),
            'total_value' => (float) ($this->total_value ?? 0),
            'status' => $statusDisplay,
            'statusCode' => $statusKey,
            'status_code' => $statusKey,
            'validationNote' => $this->cancellation_reason,
            'cancellationReason' => $this->cancellation_reason,
            'cancellation_reason' => $this->cancellation_reason,
            'notes' => $this->notes,
            'createdBy' => $this->creator?->name,
            'finalizedBy' => $this->finalizer?->name,
            'finalizedAt' => $this->finalized_at?->toIso8601String(),
            'cancelledBy' => $this->canceller?->name,
            'cancelledAt' => $this->cancelled_at?->toIso8601String(),
            'items' => GoodsIssueItemResource::collection($this->whenLoaded('items')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
