<?php

namespace App\Http\Resources\Logistics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosSaleResource extends JsonResource
{
    /**
     * Map database status to UI label.
     */
    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'completed' => 'Lunas',
            'cancelled' => 'Dibatalkan',
            'draft' => 'Draft',
            default => ucfirst($status),
        };
    }

    /**
     * Format date in Indonesian format: 15 Sep 2026
     */
    protected function formatDateIndo($date): string
    {
        if (! $date) {
            return '-';
        }
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $m = (int) $date->format('n');
        $mStr = $months[$m] ?? $date->format('M');

        return $date->format('d') . ' ' . $mStr . ' ' . $date->format('Y');
    }

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $dt = $this->transaction_date ? ($this->transaction_date instanceof \DateTimeInterface ? $this->transaction_date : \Carbon\Carbon::parse($this->transaction_date)) : null;
        $dateDisplay = $dt ? $this->formatDateIndo($dt) : '-';
        $timeDisplay = $dt ? $dt->format('H:i') . ' WIB' : '-';
        $dateTime = $dt ? $dt->format('Y-m-d H:i') : null;

        $statusUi = $this->mapStatus($this->status);

        $paymentMethod = $this->payment_method ?? 'Tunai';
        if ($this->status === 'draft' && empty($this->payment_method)) {
            $paymentMethod = 'Belum Dipilih';
        }
        $paymentMethodDisplay = $paymentMethod;
        if (strtolower($paymentMethod) === 'tunai') {
            $paymentMethodDisplay = 'Tunai (Cash)';
        }

        $itemsCount = $this->relationLoaded('items')
            ? $this->items->count()
            : ($this->items_count ?? 0);

        // Format total qty text
        $totalQty = (float) $this->total_quantity;
        $totalQtyText = $totalQty > 0 ? "{$totalQty} Unit" : '0 Unit';
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            $units = [];
            foreach ($this->items as $it) {
                $u = $it->unit ?? 'Unit';
                $units[$u] = ($units[$u] ?? 0) + (float) $it->quantity;
            }
            $unitParts = [];
            foreach ($units as $uName => $uVal) {
                $unitParts[] = "{$uVal} {$uName}";
            }
            if (! empty($unitParts)) {
                $totalQtyText = implode(', ', array_slice($unitParts, 0, 3));
            }
        }

        $customerDisplayName = $this->customer?->name ?? $this->customer_name;
        $formattedDate = $dt ? $dt->format('d-m-Y') : '-';

        return [
            'id' => $this->id,
            'legacyId' => $this->legacy_id,
            'transactionNumber' => $this->transaction_number,
            'date' => $dt ? $dt->format('Y-m-d') : null,
            'formattedDate' => $formattedDate,
            'dateDisplay' => $dateDisplay,
            'timeDisplay' => $timeDisplay,
            'dateTime' => $dateTime,
            'customerId' => $this->customer_id,
            'customerName' => $customerDisplayName,
            'customer_name' => $customerDisplayName,
            'pembeli' => $customerDisplayName,
            'customerType' => $this->customer_type ?? 'Umum',
            'customer_type' => $this->customer_type ?? 'Umum',
            'customerPhone' => '-',
            'customer_phone' => '-',
            'itemCount' => $itemsCount,
            'itemCountText' => "{$itemsCount} Jenis",
            'totalQty' => $totalQty,
            'totalQtyText' => $totalQtyText,
            'totalAmount' => (float) $this->total_amount,
            'paidAmount' => (float) $this->paid_amount,
            'changeAmount' => (float) $this->change_amount,
            'paymentMethod' => $paymentMethod,
            'paymentMethodDisplay' => $paymentMethodDisplay,
            'status' => $statusUi,
            'rawStatus' => $this->status,
            'cashier' => $this->cashier_name ?? 'Petugas Kasir',
            'location' => $this->warehouse?->name ?? 'Farmasi Pusat (Kasir 1)',
            'warehouseId' => $this->warehouse_id,
            'notes' => $this->notes,
            'voidReason' => $this->cancellation_reason ?? '',
            'completedAt' => $this->completed_at ? ($this->completed_at instanceof \DateTimeInterface ? $this->completed_at->format('Y-m-d H:i:s') : (string) $this->completed_at) : null,
            'cancelledAt' => $this->cancelled_at ? ($this->cancelled_at instanceof \DateTimeInterface ? $this->cancelled_at->format('Y-m-d H:i:s') : (string) $this->cancelled_at) : null,
            'items' => PosSaleItemResource::collection($this->whenLoaded('items')),
            'createdAt' => $this->created_at ? ($this->created_at instanceof \DateTimeInterface ? $this->created_at->format('Y-m-d H:i:s') : (string) $this->created_at) : null,
        ];
    }
}

