<?php

namespace App\Services\Logistics;

use App\Models\Logistics\StockMovement;
use App\Models\Logistics\StockOpname;
use App\Models\Logistics\StockOpnameItem;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOpnameService
{
    /**
     * Create stock opname (draft or start immediately).
     */
    public function createOpname(array $data, int $userId, bool $startImmediately = false): StockOpname
    {
        return DB::transaction(function () use ($data, $userId, $startImmediately) {
            $warehouseId = (int) ($data['warehouse_id'] ?? $data['warehouseId'] ?? 0);
            $warehouse = Warehouse::find($warehouseId);

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'warehouse_id' => ['Gudang atau lokasi yang dipilih tidak valid.'],
                ]);
            }

            $startDate = ! empty($data['start_date'] ?? $data['startDateTime'] ?? null)
                ? Carbon::parse($data['start_date'] ?? $data['startDateTime'])
                : Carbon::now();

            $opnameNumber = $this->generateOpnameNumber($startDate);

            $opname = StockOpname::create([
                'opname_number' => $opnameNumber,
                'warehouse_id' => $warehouseId,
                'title' => $data['title'] ?? ($startImmediately ? 'Audit Fisik Persediaan' : 'Draft Sesi Audit'),
                'type' => $data['type'] ?? 'Audit Rutin',
                'status' => StockOpname::STATUS_DRAFT,
                'start_date' => $startDate,
                'location_detail' => $data['location_detail'] ?? $data['locationDetail'] ?? null,
                'officer_name' => $data['officer_name'] ?? $data['officerName'] ?? 'Petugas Farmasi',
                'officer_role' => $data['officer_role'] ?? $data['officerRole'] ?? 'Staf Farmasi',
                'notes' => $data['notes'] ?? null,
                'total_items' => 0,
                'counted_items' => 0,
                'difference_count' => 0,
                'total_difference_value' => 0,
                'created_by' => $userId,
            ]);

            if ($startImmediately) {
                return $this->startOpname($opname, $userId);
            }

            return $opname;
        });
    }

    /**
     * Start physical counting and take snapshot of warehouse stocks.
     */
    public function startOpname(StockOpname $stockOpname, int $userId): StockOpname
    {
        return DB::transaction(function () use ($stockOpname) {
            /** @var StockOpname $opname */
            $opname = StockOpname::lockForUpdate()->findOrFail($stockOpname->id);

            if ($opname->status !== StockOpname::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'status' => ['Sesi stok opname sudah dimulai atau tidak berstatus draft.'],
                ]);
            }

            $warehouseStocks = WarehouseStock::where('warehouse_id', $opname->warehouse_id)
                ->where('quantity', '>', 0)
                ->with(['medicine.unit'])
                ->orderBy('medicine_id')
                ->get();

            if ($warehouseStocks->isEmpty()) {
                throw ValidationException::withMessages([
                    'warehouse_id' => ['Gudang yang dipilih tidak memiliki persediaan obat aktif untuk dilakukan stok opname.'],
                ]);
            }

            // Remove any stale detail items if present
            $opname->items()->delete();

            $totalItems = 0;
            foreach ($warehouseStocks as $stock) {
                $medicine = $stock->medicine;
                if (! $medicine) {
                    continue;
                }

                $unitPrice = (float) ($medicine->selling_price_per_unit ?? 0);

                StockOpnameItem::create([
                    'stock_opname_id' => $opname->id,
                    'warehouse_stock_id' => $stock->id,
                    'medicine_id' => $medicine->id,
                    'medicine_name' => $medicine->name,
                    'medicine_code' => $medicine->code,
                    'unit' => $medicine->unit?->name ?? 'Unit',
                    'batch_number' => $stock->batch_number,
                    'expired_date' => $stock->expired_date,
                    'system_stock' => (float) $stock->quantity,
                    'physical_stock' => null,
                    'difference' => null,
                    'unit_price' => $unitPrice,
                    'difference_value' => null,
                    'notes' => null,
                ]);

                $totalItems++;
            }

            $opname->status = StockOpname::STATUS_COUNTING;
            $opname->total_items = $totalItems;
            $opname->counted_items = 0;
            $opname->difference_count = 0;
            $opname->total_difference_value = 0;
            if (! $opname->start_date) {
                $opname->start_date = Carbon::now();
            }
            $opname->save();

            return $opname->fresh(['warehouse', 'items.medicine']);
        });
    }

    /**
     * Update physical stock count for an item.
     */
    public function updateItemCount(StockOpname $stockOpname, StockOpnameItem $item, array $data, int $userId): StockOpnameItem
    {
        return DB::transaction(function () use ($stockOpname, $item, $data, $userId) {
            /** @var StockOpname $opname */
            $opname = StockOpname::lockForUpdate()->findOrFail($stockOpname->id);

            if ($opname->status !== StockOpname::STATUS_COUNTING) {
                throw ValidationException::withMessages([
                    'status' => ['Jumlah stok fisik hanya dapat dimasukkan saat sesi berstatus sedang dihitung (counting).'],
                ]);
            }

            if ((int) $item->stock_opname_id !== (int) $opname->id) {
                throw ValidationException::withMessages([
                    'item' => ['Item obat tidak termasuk dalam sesi stok opname ini.'],
                ]);
            }

            $physicalStockRaw = $data['physical_stock'] ?? $data['physicalStock'] ?? null;
            if ($physicalStockRaw === null || ! is_numeric($physicalStockRaw)) {
                throw ValidationException::withMessages([
                    'physical_stock' => ['Jumlah stok fisik wajib diisi dan berupa angka.'],
                ]);
            }

            $physicalStock = (float) $physicalStockRaw;
            if ($physicalStock < 0) {
                throw ValidationException::withMessages([
                    'physical_stock' => ['Jumlah stok fisik tidak boleh negatif.'],
                ]);
            }

            $systemStock = (float) $item->system_stock;
            $unitPrice = (float) $item->unit_price;

            $diff = $physicalStock - $systemStock;
            $diffValue = $diff * $unitPrice;

            $item->physical_stock = $physicalStock;
            $item->difference = $diff;
            $item->difference_value = $diffValue;
            if (isset($data['notes'])) {
                $item->notes = $data['notes'];
            }
            $item->counted_by = $userId;
            $item->counted_at = Carbon::now();
            $item->save();

            // Refresh header aggregates
            $countedItems = $opname->items()->whereNotNull('physical_stock')->count();
            $diffCount = $opname->items()->whereNotNull('physical_stock')->where('difference', '!=', 0)->count();
            $totalDiffVal = $opname->items()->whereNotNull('physical_stock')->sum('difference_value');

            $opname->counted_items = $countedItems;
            $opname->difference_count = $diffCount;
            $opname->total_difference_value = $totalDiffVal;
            $opname->save();

            return $item->fresh();
        });
    }

    /**
     * Submit counted session for review.
     */
    public function submitForReview(StockOpname $stockOpname, int $userId): StockOpname
    {
        return DB::transaction(function () use ($stockOpname, $userId) {
            /** @var StockOpname $opname */
            $opname = StockOpname::lockForUpdate()->findOrFail($stockOpname->id);

            if ($opname->status !== StockOpname::STATUS_COUNTING) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya sesi dengan status sedang dihitung yang dapat diajukan untuk peninjauan.'],
                ]);
            }

            $uncountedCount = $opname->items()->whereNull('physical_stock')->count();
            if ($uncountedCount > 0) {
                throw ValidationException::withMessages([
                    'items' => ["Terdapat {$uncountedCount} item sediaan yang belum dihitung fisiknya. Seluruh item harus dihitung sebelum peninjauan."],
                ]);
            }

            $opname->status = StockOpname::STATUS_REVIEW;
            $opname->submitted_by = $userId;
            $opname->submitted_at = Carbon::now();
            $opname->save();

            return $opname->fresh(['warehouse', 'items']);
        });
    }

    /**
     * Approve discrepancy and perform stock adjustment movements.
     */
    public function approve(StockOpname $stockOpname, int $userId): StockOpname
    {
        return DB::transaction(function () use ($stockOpname, $userId) {
            /** @var StockOpname $opname */
            $opname = StockOpname::lockForUpdate()->findOrFail($stockOpname->id);

            if ($opname->status === StockOpname::STATUS_COMPLETED) {
                // Idempotent guard: already approved
                return $opname->fresh(['warehouse', 'items']);
            }

            if ($opname->status !== StockOpname::STATUS_REVIEW) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya sesi stok opname berstatus menunggu peninjauan (review) yang dapat disetujui.'],
                ]);
            }

            $now = Carbon::now();

            $items = $opname->items()->get();
            foreach ($items as $item) {
                if ($item->physical_stock === null) {
                    continue;
                }

                $diff = (float) $item->difference;
                if ($diff == 0.0) {
                    continue;
                }

                // Lock the warehouse stock record
                $stockRecord = null;
                if ($item->warehouse_stock_id) {
                    $stockRecord = WarehouseStock::lockForUpdate()->find($item->warehouse_stock_id);
                }

                if (! $stockRecord) {
                    $stockRecord = WarehouseStock::where('warehouse_id', $opname->warehouse_id)
                        ->where('medicine_id', $item->medicine_id)
                        ->when($item->batch_number, fn ($q) => $q->where('batch_number', $item->batch_number), fn ($q) => $q->whereNull('batch_number'))
                        ->when($item->expired_date, fn ($q) => $q->where('expired_date', $item->expired_date), fn ($q) => $q->whereNull('expired_date'))
                        ->lockForUpdate()
                        ->first();
                }

                $balanceBefore = $stockRecord ? (float) $stockRecord->quantity : 0.0;
                $balanceAfter = (float) $item->physical_stock;

                $delta = $balanceAfter - $balanceBefore;
                $direction = $delta >= 0 ? 'IN' : 'OUT';
                $absQty = abs($delta);

                if (! $stockRecord) {
                    $stockRecord = WarehouseStock::create([
                        'warehouse_id' => $opname->warehouse_id,
                        'medicine_id' => $item->medicine_id,
                        'quantity' => $balanceAfter,
                        'batch_number' => $item->batch_number,
                        'expired_date' => $item->expired_date,
                    ]);
                } else {
                    $stockRecord->quantity = $balanceAfter;
                    $stockRecord->save();
                }

                // Record stock movement ledger
                StockMovement::create([
                    'warehouse_id' => $opname->warehouse_id,
                    'medicine_id' => $item->medicine_id,
                    'stock_opname_id' => $opname->id,
                    'stock_opname_item_id' => $item->id,
                    'movement_type' => 'stock_opname',
                    'direction' => $direction,
                    'quantity' => $absQty,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'batch_number' => $item->batch_number,
                    'expired_date' => $item->expired_date,
                    'occurred_at' => $now,
                    'created_by' => $userId,
                ]);
            }

            $opname->status = StockOpname::STATUS_COMPLETED;
            $opname->approved_by = $userId;
            $opname->approved_at = $now;
            $opname->end_date = $now;
            $opname->save();

            return $opname->fresh(['warehouse', 'items']);
        });
    }

    /**
     * Cancel a stock opname session.
     */
    public function cancel(StockOpname $stockOpname, string $reason, int $userId): StockOpname
    {
        return DB::transaction(function () use ($stockOpname, $reason, $userId) {
            /** @var StockOpname $opname */
            $opname = StockOpname::lockForUpdate()->findOrFail($stockOpname->id);

            if ($opname->isCompleted()) {
                throw ValidationException::withMessages([
                    'status' => ['Sesi stok opname yang telah selesai tidak dapat dibatalkan.'],
                ]);
            }

            $cleanReason = trim($reason);
            if (empty($cleanReason)) {
                throw ValidationException::withMessages([
                    'cancel_reason' => ['Alasan pembatalan wajib diisi.'],
                ]);
            }

            $opname->status = StockOpname::STATUS_CANCELLED;
            $opname->cancel_reason = $cleanReason;
            $opname->cancelled_by = $userId;
            $opname->cancelled_at = Carbon::now();
            $opname->end_date = Carbon::now();
            $opname->save();

            return $opname->fresh(['warehouse', 'items']);
        });
    }

    /**
     * Delete draft stock opname.
     */
    public function deleteDraft(StockOpname $stockOpname): bool
    {
        return DB::transaction(function () use ($stockOpname) {
            /** @var StockOpname $opname */
            $opname = StockOpname::lockForUpdate()->findOrFail($stockOpname->id);

            if ($opname->status !== StockOpname::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya sesi dengan status draft yang dapat dihapus.'],
                ]);
            }

            return (bool) $opname->delete();
        });
    }

    /**
     * Generate unique opname number: SO-YYYYMM-XXXX
     */
    public function generateOpnameNumber(Carbon $date): string
    {
        $yearMonth = $date->format('Ym');
        $prefix = "SO-{$yearMonth}-";

        $lastRecord = StockOpname::withTrashed()
            ->where('opname_number', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(opname_number, -4) AS UNSIGNED) DESC')
            ->first();

        $nextSeq = 1;
        if ($lastRecord && preg_match('/-(\d{4})$/', $lastRecord->opname_number, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        $formatted = $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);

        while (StockOpname::withTrashed()->where('opname_number', $formatted)->exists()) {
            $nextSeq++;
            $formatted = $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        }

        return $formatted;
    }

    /**
     * Calculate summary metrics for the dashboard cards.
     */
    public function getSummary(?int $warehouseId = null): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $baseQuery = StockOpname::query();
        if ($warehouseId) {
            $baseQuery->where('warehouse_id', $warehouseId);
        }

        // 1. Sedang Berlangsung (counting)
        $inProgressQuery = (clone $baseQuery)->where('status', StockOpname::STATUS_COUNTING);
        $inProgressSessionsCount = $inProgressQuery->count();
        $inProgressItemsCount = (int) $inProgressQuery->sum('total_items');

        // 2. Menunggu Peninjauan (review)
        $pendingReviewQuery = (clone $baseQuery)->where('status', StockOpname::STATUS_REVIEW);
        $pendingReviewWarehousesCount = $pendingReviewQuery->distinct('warehouse_id')->count('warehouse_id');
        $pendingReviewSessionsCount = $pendingReviewQuery->count();

        // 3. Selesai Bulan Ini (completed this month)
        $completedThisMonthQuery = (clone $baseQuery)
            ->where('status', StockOpname::STATUS_COMPLETED)
            ->whereBetween('approved_at', [$startOfMonth, $endOfMonth]);
        $completedCount = $completedThisMonthQuery->count();

        // 4. Total Selisih Ditemukan (discrepancy items & value)
        $activeOpnameIds = (clone $baseQuery)
            ->whereIn('status', [StockOpname::STATUS_COUNTING, StockOpname::STATUS_REVIEW, StockOpname::STATUS_COMPLETED])
            ->pluck('id');

        $discrepancyItemsCount = StockOpnameItem::whereIn('stock_opname_id', $activeOpnameIds)
            ->whereNotNull('physical_stock')
            ->where('difference', '!=', 0)
            ->count();

        $discrepancyTotalVal = (float) StockOpnameItem::whereIn('stock_opname_id', $activeOpnameIds)
            ->whereNotNull('physical_stock')
            ->sum('difference_value');

        $plusCount = StockOpnameItem::whereIn('stock_opname_id', $activeOpnameIds)
            ->whereNotNull('physical_stock')
            ->where('difference', '>', 0)
            ->count();

        $minusCount = StockOpnameItem::whereIn('stock_opname_id', $activeOpnameIds)
            ->whereNotNull('physical_stock')
            ->where('difference', '<', 0)
            ->count();

        $formattedDiscrepancyValue = 'Rp'.number_format(abs($discrepancyTotalVal), 0, ',', '.');

        return [
            'in_progress_sessions_count' => $inProgressSessionsCount,
            'in_progress_items_count' => $inProgressItemsCount,
            'pending_review_warehouses_count' => $pendingReviewWarehousesCount,
            'pending_review_sessions_count' => $pendingReviewSessionsCount,
            'completed_this_month_sessions_count' => $completedCount,
            'discrepancy_items_count' => $discrepancyItemsCount,
            'discrepancy_value' => $discrepancyTotalVal,
            'formatted_discrepancy_value' => $formattedDiscrepancyValue,
            'plus_count' => $plusCount,
            'minus_count' => $minusCount,
            // Preformatted card objects for frontend components
            'inProgressSessions' => "{$inProgressSessionsCount} Sesi",
            'inProgressNote' => $inProgressItemsCount > 0
                ? number_format($inProgressItemsCount, 0, ',', '.').' item dalam proses hitung fisik'
                : 'Tidak ada sesi hitung aktif',
            'pendingReviewWarehouses' => "{$pendingReviewWarehousesCount} Gudang",
            'pendingReviewNote' => 'Perlu persetujuan Apoteker & PJ Logistik',
            'completedThisMonthSessions' => "{$completedCount} Sesi",
            'completedThisMonthNote' => 'Akurasi stok terverifikasi',
            'discrepancyItems' => "{$discrepancyItemsCount} Item",
            'discrepancyValue' => $formattedDiscrepancyValue,
            'discrepancyRatio' => "{$plusCount}+ / {$minusCount}-",
        ];
    }
}
