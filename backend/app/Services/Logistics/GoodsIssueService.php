<?php

namespace App\Services\Logistics;

use App\Models\Employee;
use App\Models\Logistics\GoodsIssue;
use App\Models\Logistics\GoodsIssueItem;
use App\Models\Logistics\StockMovement;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsIssueService
{
    /**
     * Map common purpose texts to code.
     */
    public function resolvePurposeCode(string $purpose): string
    {
        $lower = strtolower($purpose);
        if (str_contains($lower, 'rusak')) {
            return 'damaged';
        }
        if (str_contains($lower, 'kedaluwarsa') || str_contains($lower, 'pemusnahan')) {
            return 'expired';
        }
        if (str_contains($lower, 'sosial') || str_contains($lower, 'baksos')) {
            return 'social';
        }
        if (str_contains($lower, 'buffer')) {
            return 'buffer';
        }
        if (str_contains($lower, 'penelitian') || str_contains($lower, 'uji')) {
            return 'research';
        }
        if (str_contains($lower, 'internal')) {
            return 'internal';
        }

        return 'other';
    }

    /**
     * Create goods issue (either as draft or immediately completed).
     *
     * @throws ValidationException
     */
    public function createIssue(array $data, int $userId, bool $finalize = false): GoodsIssue
    {
        return DB::transaction(function () use ($data, $userId, $finalize) {
            $warehouseId = (int) ($data['warehouse_id'] ?? $data['warehouseId']);
            $warehouse = Warehouse::find($warehouseId);
            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'warehouse_id' => ['Gudang asal tidak ditemukan.'],
                ]);
            }

            $itemsData = $data['items'] ?? [];

            // Check duplicate medicine + batch in items payload if provided
            if (! empty($itemsData)) {
                $itemKeys = [];
                foreach ($itemsData as $idx => $item) {
                    $medId = (int) ($item['medicine_id'] ?? $item['medicineId'] ?? 0);
                    $batch = trim($item['batch_number'] ?? $item['batchNumber'] ?? '');
                    $key = "{$medId}#{$batch}";
                    if (isset($itemKeys[$key])) {
                        throw ValidationException::withMessages([
                            "items.{$idx}.medicine_id" => ['Obat dengan batch yang sama tidak boleh dimasukkan lebih dari satu kali dalam satu dokumen.'],
                        ]);
                    }
                    $itemKeys[$key] = true;
                }
            }

            $issueDate = ! empty($data['issue_date'] ?? null)
                ? Carbon::parse($data['issue_date'])
                : Carbon::now();

            $issueNumber = $this->generateIssueNumber($issueDate);
            $purpose = $data['purpose'] ?? 'Kebutuhan Internal';
            $purposeCode = $data['purpose_code'] ?? $this->resolvePurposeCode($purpose);

            $officerName = $data['officer_name'] ?? $data['officerName'] ?? 'Petugas Logistik';
            $officerId = isset($data['officer_id']) || isset($data['officerId'])
                ? (int) ($data['officer_id'] ?? $data['officerId'])
                : null;
            $officerRole = $data['officer_role'] ?? $data['officerRole'] ?? null;

            if ($officerId && empty($officerRole)) {
                $employee = Employee::with('position')->find($officerId);
                if ($employee) {
                    $officerRole = $employee->position?->name ?? 'Petugas Logistik Farmasi';
                }
            }

            $goodsIssue = GoodsIssue::create([
                'issue_number' => $issueNumber,
                'issue_date' => $issueDate,
                'warehouse_id' => $warehouseId,
                'location_detail' => $data['location_detail'] ?? $data['locationDetail'] ?? null,
                'officer_name' => $officerName,
                'officer_id' => $officerId,
                'officer_role' => $officerRole,
                'purpose' => $purpose,
                'purpose_code' => $purposeCode,
                'reference_number' => $data['reference_number'] ?? $data['referenceNumber'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'total_quantity' => 0,
                'total_value' => 0,
                'created_by' => $userId,
            ]);

            $totalQty = 0.0;
            $totalVal = 0.0;

            foreach ($itemsData as $idx => $item) {
                $medId = (int) ($item['medicine_id'] ?? $item['medicineId']);
                $qty = (float) ($item['quantity'] ?? 0);
                $batch = isset($item['batch_number']) || isset($item['batchNumber'])
                    ? trim($item['batch_number'] ?? $item['batchNumber'] ?? '')
                    : null;
                $expDate = ! empty($item['expired_date'] ?? $item['expDate'] ?? null)
                    ? Carbon::parse($item['expired_date'] ?? $item['expDate'])
                    : null;
                $stockId = isset($item['warehouse_stock_id']) || isset($item['warehouseStockId'])
                    ? (int) ($item['warehouse_stock_id'] ?? $item['warehouseStockId'])
                    : null;

                if ($qty <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.quantity" => ['Kuantitas pengeluaran harus lebih besar dari nol.'],
                    ]);
                }

                $medicine = Medicine::with('unit')->find($medId);
                if (! $medicine) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.medicine_id" => ['Obat tidak ditemukan.'],
                    ]);
                }

                // If stockId is provided, find that specific stock; otherwise find by warehouse + medicine + batch
                $stock = null;
                if ($stockId) {
                    $stock = WarehouseStock::where('id', $stockId)
                        ->where('warehouse_id', $warehouseId)
                        ->first();
                }

                if (! $stock && ! empty($batch)) {
                    $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                        ->where('medicine_id', $medId)
                        ->where('batch_number', $batch)
                        ->first();
                }

                if (! $stock) {
                    $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                        ->where('medicine_id', $medId)
                        ->orderByDesc('quantity')
                        ->first();
                }

                $batchNumber = $stock?->batch_number ?? $batch ?? 'REG-'.date('Ymd');
                $expiredDate = $stock?->expired_date ?? $expDate ?? Carbon::now()->addYear();
                $unit = $stock?->medicine?->unit?->name ?? $medicine->unit?->name ?? 'Unit';
                $unitPrice = (float) ($stock?->selling_price ?? $medicine->selling_price_per_unit ?? 0);
                $subtotal = $qty * $unitPrice;

                GoodsIssueItem::create([
                    'goods_issue_id' => $goodsIssue->id,
                    'warehouse_stock_id' => $stock?->id,
                    'medicine_id' => $medicine->id,
                    'medicine_name' => $medicine->name,
                    'batch_number' => $batchNumber,
                    'expired_date' => $expiredDate,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                ]);

                $totalQty += $qty;
                $totalVal += $subtotal;
            }

            $goodsIssue->total_quantity = $totalQty;
            $goodsIssue->total_value = $totalVal;
            $goodsIssue->save();

            if ($finalize && ! empty($itemsData)) {
                $this->finalize($goodsIssue, $userId);
            }

            return $goodsIssue->fresh(['warehouse', 'officer', 'creator', 'finalizer', 'items.medicine.unit', 'items.warehouseStock']);
        });
    }

    /**
     * Update draft goods issue.
     *
     * @throws ValidationException
     */
    public function updateDraft(GoodsIssue $goodsIssue, array $data, int $userId, bool $finalize = false): GoodsIssue
    {
        if (! $goodsIssue->isDraft()) {
            throw ValidationException::withMessages([
                'status' => ['Dokumen pengeluaran yang sudah difinalisasi atau dibatalkan tidak dapat diedit.'],
            ]);
        }

        return DB::transaction(function () use ($goodsIssue, $data, $userId, $finalize) {
            $goodsIssue = GoodsIssue::lockForUpdate()->find($goodsIssue->id);
            if (! $goodsIssue->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => ['Dokumen pengeluaran yang sudah difinalisasi atau dibatalkan tidak dapat diedit.'],
                ]);
            }

            $warehouseId = isset($data['warehouse_id']) || isset($data['warehouseId'])
                ? (int) ($data['warehouse_id'] ?? $data['warehouseId'])
                : $goodsIssue->warehouse_id;

            $warehouse = Warehouse::find($warehouseId);
            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'warehouse_id' => ['Gudang asal tidak ditemukan.'],
                ]);
            }

            $issueDate = ! empty($data['issue_date'] ?? null)
                ? Carbon::parse($data['issue_date'])
                : $goodsIssue->issue_date;

            $purpose = $data['purpose'] ?? $goodsIssue->purpose;
            $purposeCode = $data['purpose_code'] ?? $this->resolvePurposeCode($purpose);

            $officerName = $data['officer_name'] ?? $data['officerName'] ?? $goodsIssue->officer_name;
            $officerId = isset($data['officer_id']) || isset($data['officerId'])
                ? (int) ($data['officer_id'] ?? $data['officerId'])
                : $goodsIssue->officer_id;
            $officerRole = $data['officer_role'] ?? $data['officerRole'] ?? $goodsIssue->officer_role;

            $goodsIssue->update([
                'warehouse_id' => $warehouseId,
                'issue_date' => $issueDate,
                'location_detail' => $data['location_detail'] ?? $data['locationDetail'] ?? $goodsIssue->location_detail,
                'officer_name' => $officerName,
                'officer_id' => $officerId,
                'officer_role' => $officerRole,
                'purpose' => $purpose,
                'purpose_code' => $purposeCode,
                'reference_number' => $data['reference_number'] ?? $data['referenceNumber'] ?? $goodsIssue->reference_number,
                'notes' => $data['notes'] ?? $goodsIssue->notes,
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                $itemsData = $data['items'];
                if (empty($itemsData)) {
                    throw ValidationException::withMessages([
                        'items' => ['Minimal satu item obat harus ditambahkan.'],
                    ]);
                }

                // Check duplicate medicine + batch
                $itemKeys = [];
                foreach ($itemsData as $idx => $item) {
                    $medId = (int) ($item['medicine_id'] ?? $item['medicineId'] ?? 0);
                    $batch = trim($item['batch_number'] ?? $item['batchNumber'] ?? '');
                    $key = "{$medId}#{$batch}";
                    if (isset($itemKeys[$key])) {
                        throw ValidationException::withMessages([
                            "items.{$idx}.medicine_id" => ['Obat dengan batch yang sama tidak boleh dimasukkan lebih dari satu kali dalam satu dokumen.'],
                        ]);
                    }
                    $itemKeys[$key] = true;
                }

                // Delete old items and insert updated items
                $goodsIssue->items()->delete();

                $totalQty = 0.0;
                $totalVal = 0.0;

                foreach ($itemsData as $idx => $item) {
                    $medId = (int) ($item['medicine_id'] ?? $item['medicineId']);
                    $qty = (float) ($item['quantity'] ?? 0);
                    $batch = isset($item['batch_number']) || isset($item['batchNumber'])
                        ? trim($item['batch_number'] ?? $item['batchNumber'] ?? '')
                        : null;
                    $expDate = ! empty($item['expired_date'] ?? $item['expDate'] ?? null)
                        ? Carbon::parse($item['expired_date'] ?? $item['expDate'])
                        : null;
                    $stockId = isset($item['warehouse_stock_id']) || isset($item['warehouseStockId'])
                        ? (int) ($item['warehouse_stock_id'] ?? $item['warehouseStockId'])
                        : null;

                    if ($qty <= 0) {
                        throw ValidationException::withMessages([
                            "items.{$idx}.quantity" => ['Kuantitas pengeluaran harus lebih besar dari nol.'],
                        ]);
                    }

                    $medicine = Medicine::with('unit')->find($medId);
                    if (! $medicine) {
                        throw ValidationException::withMessages([
                            "items.{$idx}.medicine_id" => ['Obat tidak ditemukan.'],
                        ]);
                    }

                    $stock = null;
                    if ($stockId) {
                        $stock = WarehouseStock::where('id', $stockId)
                            ->where('warehouse_id', $warehouseId)
                            ->first();
                    }

                    if (! $stock && ! empty($batch)) {
                        $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                            ->where('medicine_id', $medId)
                            ->where('batch_number', $batch)
                            ->first();
                    }

                    if (! $stock) {
                        $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                            ->where('medicine_id', $medId)
                            ->orderByDesc('quantity')
                            ->first();
                    }

                    $batchNumber = $stock?->batch_number ?? $batch ?? 'REG-'.date('Ymd');
                    $expiredDate = $stock?->expired_date ?? $expDate ?? Carbon::now()->addYear();
                    $unit = $stock?->medicine?->unit?->name ?? $medicine->unit?->name ?? 'Unit';
                    $unitPrice = (float) ($stock?->selling_price ?? $medicine->selling_price_per_unit ?? 0);
                    $subtotal = $qty * $unitPrice;

                    GoodsIssueItem::create([
                        'goods_issue_id' => $goodsIssue->id,
                        'warehouse_stock_id' => $stock?->id,
                        'medicine_id' => $medicine->id,
                        'medicine_name' => $medicine->name,
                        'batch_number' => $batchNumber,
                        'expired_date' => $expiredDate,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'notes' => $item['notes'] ?? null,
                    ]);

                    $totalQty += $qty;
                    $totalVal += $subtotal;
                }

                $goodsIssue->total_quantity = $totalQty;
                $goodsIssue->total_value = $totalVal;
                $goodsIssue->save();
            }

            if ($finalize && ! empty($itemsData)) {
                $this->finalize($goodsIssue, $userId);
            }

            return $goodsIssue->fresh(['warehouse', 'officer', 'creator', 'finalizer', 'items.medicine.unit', 'items.warehouseStock']);
        });
    }

    /**
     * Finalize draft goods issue: atomically validate stocks, deduct quantities, record stock movements.
     *
     * @throws ValidationException
     */
    public function finalize(GoodsIssue $goodsIssue, int $userId): GoodsIssue
    {
        return DB::transaction(function () use ($goodsIssue, $userId) {
            $issue = GoodsIssue::with('items')->lockForUpdate()->find($goodsIssue->id);
            if (! $issue) {
                throw ValidationException::withMessages([
                    'goods_issue' => ['Dokumen pengeluaran tidak ditemukan.'],
                ]);
            }

            if ($issue->status === 'completed') {
                // Prevent duplicate finalization from reducing stock twice
                return $issue;
            }

            if ($issue->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'status' => ['Dokumen yang sudah dibatalkan tidak dapat difinalisasi.'],
                ]);
            }

            if ($issue->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['Dokumen pengeluaran tidak memiliki item untuk dikeluarkan.'],
                ]);
            }

            $warehouseId = $issue->warehouse_id;

            // Validate and deduct stocks for each item
            foreach ($issue->items as $idx => $item) {
                if ($item->quantity <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.quantity" => ["Kuantitas untuk obat {$item->medicine_name} harus lebih besar dari nol."],
                    ]);
                }

                $stock = null;
                if ($item->warehouse_stock_id) {
                    $stock = WarehouseStock::lockForUpdate()->find($item->warehouse_stock_id);
                }

                // If stock not found by ID or warehouse mismatched, find by warehouse + medicine + batch
                if (! $stock || $stock->warehouse_id !== $warehouseId) {
                    $stockQuery = WarehouseStock::where('warehouse_id', $warehouseId)
                        ->where('medicine_id', $item->medicine_id)
                        ->lockForUpdate();

                    if (! empty($item->batch_number)) {
                        $stockQuery->where('batch_number', $item->batch_number);
                    }

                    $stock = $stockQuery->first();
                }

                if (! $stock || $stock->warehouse_id !== $warehouseId) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.medicine_id" => ["Stok untuk obat {$item->medicine_name} (Batch: {$item->batch_number}) tidak ditemukan pada gudang terpilih."],
                    ]);
                }

                if ($stock->quantity < $item->quantity) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.quantity" => ["Stok tidak mencukupi untuk {$item->medicine_name}. Tersedia: {$stock->quantity}, diminta: {$item->quantity}."],
                    ]);
                }

                $issueDate = $issue->issue_date
                    ? Carbon::parse($issue->issue_date)->toDateString()
                    : Carbon::today()->toDateString();

                if ($stock->expired_date && Carbon::parse($stock->expired_date)->toDateString() < $issueDate) {
                    $formattedExp = Carbon::parse($stock->expired_date)->format('d-m-Y');
                    throw ValidationException::withMessages([
                        "items.{$idx}.medicine_id" => ["Sediaan {$item->medicine_name} (Batch: {$stock->batch_number}) sudah kedaluwarsa pada {$formattedExp} dan tidak dapat dikeluarkan."],
                    ]);
                }

                $balanceBefore = (float) $stock->quantity;
                $balanceAfter = $balanceBefore - (float) $item->quantity;

                // Deduct stock
                $stock->quantity = $balanceAfter;
                $stock->save();

                // Connect item to exact stock record if not set
                if ($item->warehouse_stock_id !== $stock->id) {
                    $item->warehouse_stock_id = $stock->id;
                    $item->save();
                }

                // Create Stock Movement (Kartu Stok)
                StockMovement::create([
                    'warehouse_id' => $warehouseId,
                    'medicine_id' => $item->medicine_id,
                    'warehouse_stock_id' => $stock->id,
                    'goods_issue_id' => $issue->id,
                    'goods_issue_item_id' => $item->id,
                    'movement_type' => 'goods_issue',
                    'direction' => 'OUT',
                    'quantity' => $item->quantity,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'batch_number' => $stock->batch_number,
                    'expired_date' => $stock->expired_date,
                    'occurred_at' => $issue->issue_date ?? Carbon::now(),
                    'created_by' => $userId,
                ]);
            }

            $issue->status = 'completed';
            $issue->finalized_by = $userId;
            $issue->finalized_at = Carbon::now();
            $issue->save();

            return $issue->fresh(['warehouse', 'officer', 'creator', 'finalizer', 'items.medicine.unit', 'items.warehouseStock']);
        });
    }

    /**
     * Cancel goods issue: if completed, atomically restore stock and record reversal movements.
     *
     * @throws ValidationException
     */
    public function cancel(GoodsIssue $goodsIssue, string $reason, int $userId): GoodsIssue
    {
        return DB::transaction(function () use ($goodsIssue, $reason, $userId) {
            $issue = GoodsIssue::with('items')->lockForUpdate()->find($goodsIssue->id);
            if (! $issue) {
                throw ValidationException::withMessages([
                    'goods_issue' => ['Dokumen pengeluaran tidak ditemukan.'],
                ]);
            }

            if ($issue->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => ['Transaksi yang sudah dibatalkan tidak dapat dibatalkan kembali.'],
                ]);
            }

            // If the issue was previously completed, reverse stocks and record reversal movements
            if ($issue->isCompleted()) {
                foreach ($issue->items as $item) {
                    $stock = null;
                    if ($item->warehouse_stock_id) {
                        $stock = WarehouseStock::lockForUpdate()->find($item->warehouse_stock_id);
                    }

                    if (! $stock) {
                        $stock = WarehouseStock::where('warehouse_id', $issue->warehouse_id)
                            ->where('medicine_id', $item->medicine_id)
                            ->where('batch_number', $item->batch_number)
                            ->lockForUpdate()
                            ->first();
                    }

                    if ($stock) {
                        $balanceBefore = (float) $stock->quantity;
                        $balanceAfter = $balanceBefore + (float) $item->quantity;

                        $stock->quantity = $balanceAfter;
                        $stock->save();

                        // Create reversal movement
                        StockMovement::create([
                            'warehouse_id' => $issue->warehouse_id,
                            'medicine_id' => $item->medicine_id,
                            'warehouse_stock_id' => $stock->id,
                            'goods_issue_id' => $issue->id,
                            'goods_issue_item_id' => $item->id,
                            'movement_type' => 'goods_issue_cancellation',
                            'direction' => 'IN',
                            'quantity' => $item->quantity,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceAfter,
                            'batch_number' => $stock->batch_number,
                            'expired_date' => $stock->expired_date,
                            'occurred_at' => Carbon::now(),
                            'created_by' => $userId,
                        ]);
                    }
                }
            }

            $issue->status = 'cancelled';
            $issue->cancelled_by = $userId;
            $issue->cancelled_at = Carbon::now();
            $issue->cancellation_reason = $reason;
            $issue->save();

            return $issue->fresh(['warehouse', 'officer', 'creator', 'finalizer', 'canceller', 'items.medicine.unit', 'items.warehouseStock']);
        });
    }

    /**
     * Delete draft goods issue.
     *
     * @throws ValidationException
     */
    public function deleteDraft(GoodsIssue $goodsIssue): bool
    {
        if (! $goodsIssue->isDraft()) {
            throw ValidationException::withMessages([
                'status' => ['Hanya dokumen berstatus draft yang dapat dihapus.'],
            ]);
        }

        return DB::transaction(function () use ($goodsIssue) {
            $issue = GoodsIssue::lockForUpdate()->find($goodsIssue->id);
            if (! $issue->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya dokumen berstatus draft yang dapat dihapus.'],
                ]);
            }

            $issue->items()->delete();

            return $issue->delete();
        });
    }

    /**
     * Add single item to goods issue.
     *
     * @throws ValidationException
     */
    public function addItem(GoodsIssue $goodsIssue, array $data, int $userId): GoodsIssueItem
    {
        return DB::transaction(function () use ($goodsIssue, $data, $userId) {
            $issue = GoodsIssue::lockForUpdate()->find($goodsIssue->id);
            if (! $issue) {
                throw ValidationException::withMessages([
                    'goods_issue' => ['Dokumen pengeluaran tidak ditemukan.'],
                ]);
            }

            if ($issue->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => ['Dokumen yang sudah dibatalkan tidak dapat ditambahkan item.'],
                ]);
            }

            $medicineId = (int) ($data['medicine_id'] ?? $data['medicineId'] ?? 0);
            $medicine = Medicine::with('unit')->find($medicineId);
            if (! $medicine) {
                throw ValidationException::withMessages([
                    'medicine_id' => ['Obat / sediaan tidak ditemukan.'],
                ]);
            }

            $warehouseId = isset($data['warehouse_id']) || isset($data['warehouseId'])
                ? (int) ($data['warehouse_id'] ?? $data['warehouseId'])
                : $issue->warehouse_id;

            $warehouse = Warehouse::find($warehouseId);
            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'warehouse_id' => ['Gudang tidak ditemukan.'],
                ]);
            }

            $quantity = (float) ($data['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['Jumlah sediaan harus lebih besar dari 0.'],
                ]);
            }

            // Find stock in specified warehouse (by stock_id or by medicine_id)
            $stock = null;
            if (! empty($data['stock_id']) || ! empty($data['warehouse_stock_id'])) {
                $stockId = (int) ($data['stock_id'] ?? $data['warehouse_stock_id']);
                $stock = WarehouseStock::where('id', $stockId)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    throw ValidationException::withMessages([
                        'stock_id' => ["Persediaan stok terpilih tidak valid atau bukan milik gudang {$warehouse->name}."],
                    ]);
                }

                if ($stock->medicine_id !== $medicineId) {
                    throw ValidationException::withMessages([
                        'medicine_id' => ["Data stok tidak cocok dengan sediaan {$medicine->name}."],
                    ]);
                }
            }

            if (! $stock) {
                $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                    ->where('medicine_id', $medicineId)
                    ->where('quantity', '>', 0)
                    ->orderByDesc('quantity')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $stock) {
                $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                    ->where('medicine_id', $medicineId)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $stock) {
                throw ValidationException::withMessages([
                    'medicine_id' => ["Stok untuk sediaan {$medicine->name} tidak ditemukan di {$warehouse->name}."],
                ]);
            }

            if ($stock->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ["Stok tidak mencukupi untuk {$medicine->name}. Tersedia: {$stock->quantity}, diminta: {$quantity}."],
                ]);
            }

            // Expiration validation against issue date
            $issueDate = $issue->issue_date
                ? Carbon::parse($issue->issue_date)->toDateString()
                : Carbon::today()->toDateString();

            if ($stock->expired_date && Carbon::parse($stock->expired_date)->toDateString() < $issueDate) {
                $formattedExp = Carbon::parse($stock->expired_date)->format('d-m-Y');
                throw ValidationException::withMessages([
                    'stock_id' => ["Sediaan {$medicine->name} (Batch: {$stock->batch_number}) sudah kedaluwarsa pada {$formattedExp} dan tidak dapat dikeluarkan untuk transaksi ini."],
                ]);
            }

            $unitPrice = (float) (($stock->selling_price !== null && (float) $stock->selling_price > 0)
                ? $stock->selling_price
                : ($medicine->selling_price_per_unit ?? 0));
            $subtotal = $quantity * $unitPrice;
            $unit = $stock->medicine?->unit?->name ?? $medicine->unit?->name ?? 'Unit';

            $item = GoodsIssueItem::create([
                'goods_issue_id' => $issue->id,
                'warehouse_stock_id' => $stock->id,
                'medicine_id' => $medicine->id,
                'medicine_name' => $medicine->name,
                'batch_number' => $stock->batch_number ?? 'REG-'.date('Ymd'),
                'expired_date' => $stock->expired_date ?? Carbon::now()->addYear(),
                'quantity' => $quantity,
                'unit' => $unit,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'notes' => $data['notes'] ?? null,
            ]);

            // If issue is completed, deduct stock immediately and create stock movement
            if ($issue->isCompleted()) {
                $balanceBefore = (float) $stock->quantity;
                $balanceAfter = $balanceBefore - $quantity;
                $stock->quantity = $balanceAfter;
                $stock->save();

                StockMovement::create([
                    'warehouse_id' => $warehouseId,
                    'medicine_id' => $medicine->id,
                    'warehouse_stock_id' => $stock->id,
                    'goods_issue_id' => $issue->id,
                    'goods_issue_item_id' => $item->id,
                    'movement_type' => 'goods_issue',
                    'direction' => 'OUT',
                    'quantity' => $quantity,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'batch_number' => $stock->batch_number,
                    'expired_date' => $stock->expired_date,
                    'occurred_at' => $issue->issue_date ?? Carbon::now(),
                    'created_by' => $userId,
                ]);
            }

            $issue->total_quantity = (float) $issue->items()->sum('quantity');
            $issue->total_value = (float) $issue->items()->sum('subtotal');
            $issue->save();

            return $item->fresh(['goodsIssue', 'warehouseStock.warehouse', 'medicine.unit']);
        });
    }

    /**
     * Update item on goods issue.
     *
     * @throws ValidationException
     */
    public function updateItem(GoodsIssue $goodsIssue, GoodsIssueItem $item, array $data, int $userId): GoodsIssueItem
    {
        return DB::transaction(function () use ($goodsIssue, $item, $data, $userId) {
            $item = GoodsIssueItem::with('warehouseStock')->lockForUpdate()->find($item->id);
            if (! $item || $item->goods_issue_id !== $goodsIssue->id) {
                throw ValidationException::withMessages([
                    'item' => ['Item detail pengeluaran tidak ditemukan.'],
                ]);
            }

            $issue = GoodsIssue::lockForUpdate()->find($goodsIssue->id);
            if ($issue->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => ['Dokumen yang sudah dibatalkan tidak dapat diubah.'],
                ]);
            }

            $newQuantity = (float) ($data['quantity'] ?? $item->quantity);
            if ($newQuantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['Jumlah sediaan harus lebih besar dari 0.'],
                ]);
            }

            $diff = $newQuantity - (float) $item->quantity;

            if ($issue->isCompleted() && $diff != 0) {
                $stock = $item->warehouseStock ? WarehouseStock::lockForUpdate()->find($item->warehouse_stock_id) : null;
                if (! $stock) {
                    $stock = WarehouseStock::where('warehouse_id', $issue->warehouse_id)
                        ->where('medicine_id', $item->medicine_id)
                        ->lockForUpdate()
                        ->first();
                }

                if ($diff > 0 && $stock && $stock->quantity < $diff) {
                    throw ValidationException::withMessages([
                        'quantity' => ["Stok tidak mencukupi untuk penambahan {$item->medicine_name}. Tersedia: {$stock->quantity}, tambahan diminta: {$diff}."],
                    ]);
                }

                if ($stock) {
                    $balanceBefore = (float) $stock->quantity;
                    $balanceAfter = $balanceBefore - $diff;
                    $stock->quantity = $balanceAfter;
                    $stock->save();

                    StockMovement::create([
                        'warehouse_id' => $stock->warehouse_id,
                        'medicine_id' => $item->medicine_id,
                        'warehouse_stock_id' => $stock->id,
                        'goods_issue_id' => $issue->id,
                        'goods_issue_item_id' => $item->id,
                        'movement_type' => 'goods_issue_adjustment',
                        'direction' => $diff > 0 ? 'OUT' : 'IN',
                        'quantity' => abs($diff),
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'batch_number' => $stock->batch_number,
                        'expired_date' => $stock->expired_date,
                        'occurred_at' => Carbon::now(),
                        'created_by' => $userId,
                    ]);
                }
            }

            $item->quantity = $newQuantity;
            $item->subtotal = $newQuantity * (float) $item->unit_price;
            $item->save();

            $issue->total_quantity = (float) $issue->items()->sum('quantity');
            $issue->total_value = (float) $issue->items()->sum('subtotal');
            $issue->save();

            return $item->fresh(['goodsIssue', 'warehouseStock.warehouse', 'medicine.unit']);
        });
    }

    /**
     * Delete item from goods issue.
     *
     * @throws ValidationException
     */
    public function deleteItem(GoodsIssue $goodsIssue, GoodsIssueItem $item, int $userId): bool
    {
        return DB::transaction(function () use ($goodsIssue, $item, $userId) {
            $item = GoodsIssueItem::with('warehouseStock')->lockForUpdate()->find($item->id);
            if (! $item || $item->goods_issue_id !== $goodsIssue->id) {
                throw ValidationException::withMessages([
                    'item' => ['Item detail pengeluaran tidak ditemukan.'],
                ]);
            }

            $issue = GoodsIssue::lockForUpdate()->find($goodsIssue->id);
            if ($issue->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => ['Dokumen yang sudah dibatalkan tidak dapat diubah.'],
                ]);
            }

            if ($issue->isCompleted()) {
                $stock = $item->warehouseStock ? WarehouseStock::lockForUpdate()->find($item->warehouse_stock_id) : null;
                if (! $stock) {
                    $stock = WarehouseStock::where('warehouse_id', $issue->warehouse_id)
                        ->where('medicine_id', $item->medicine_id)
                        ->lockForUpdate()
                        ->first();
                }

                if ($stock) {
                    $balanceBefore = (float) $stock->quantity;
                    $balanceAfter = $balanceBefore + (float) $item->quantity;
                    $stock->quantity = $balanceAfter;
                    $stock->save();

                    StockMovement::create([
                        'warehouse_id' => $stock->warehouse_id,
                        'medicine_id' => $item->medicine_id,
                        'warehouse_stock_id' => $stock->id,
                        'goods_issue_id' => $issue->id,
                        'goods_issue_item_id' => $item->id,
                        'movement_type' => 'goods_issue_item_removal',
                        'direction' => 'IN',
                        'quantity' => $item->quantity,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'batch_number' => $stock->batch_number,
                        'expired_date' => $stock->expired_date,
                        'occurred_at' => Carbon::now(),
                        'created_by' => $userId,
                    ]);
                }
            }

            $item->delete();

            $issue->total_quantity = (float) $issue->items()->sum('quantity');
            $issue->total_value = (float) $issue->items()->sum('subtotal');
            $issue->save();

            return true;
        });
    }

    /**
     * Get summary metrics for goods issues.
     */
    public function getSummary(array $filters = []): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // 1. Today issues count (completed issues today)
        $todayCount = GoodsIssue::where('status', 'completed')
            ->whereDate('issue_date', $today)
            ->count();

        // 2. Total items issued in current month
        $monthItemSum = GoodsIssueItem::whereHas('goodsIssue', function ($q) use ($startOfMonth, $endOfMonth) {
            $q->where('status', 'completed')
                ->whereBetween('issue_date', [$startOfMonth, $endOfMonth]);
        })->sum('quantity');

        // 3. Damaged items in current month (or all completed)
        $damagedItemSum = GoodsIssueItem::whereHas('goodsIssue', function ($q) use ($startOfMonth, $endOfMonth) {
            $q->where('status', 'completed')
                ->where(function ($sub) {
                    $sub->where('purpose_code', 'damaged')
                        ->orWhere('purpose', 'like', '%rusak%');
                })
                ->whereBetween('issue_date', [$startOfMonth, $endOfMonth]);
        })->sum('quantity');

        // 4. Expired/destroyed items in current month (or all completed)
        $expiredItemSum = GoodsIssueItem::whereHas('goodsIssue', function ($q) use ($startOfMonth, $endOfMonth) {
            $q->where('status', 'completed')
                ->where(function ($sub) {
                    $sub->where('purpose_code', 'expired')
                        ->orWhere('purpose', 'like', '%kedaluwarsa%')
                        ->orWhere('purpose', 'like', '%pemusnahan%');
                })
                ->whereBetween('issue_date', [$startOfMonth, $endOfMonth]);
        })->sum('quantity');

        return [
            'todayIssues' => number_format((int) $todayCount, 0, ',', '.'),
            'todayUnit' => 'Dokumen',
            'todayNote' => 'Disetujui hari ini (Shift 1 & 2)',
            'totalItemOut' => number_format((float) $monthItemSum, 0, ',', '.'),
            'totalItemUnit' => 'Unit',
            'totalItemNote' => 'Akumulasi fisik bulan berjalan',
            'damagedItem' => number_format((float) $damagedItemSum, 0, ',', '.'),
            'damagedUnit' => 'Unit',
            'damagedNote' => 'Kerusakan kemasan & pecah',
            'expiredItem' => number_format((float) $expiredItemSum, 0, ',', '.'),
            'expiredUnit' => 'Unit',
            'expiredNote' => 'Karantina & pemusnahan BPOM',
        ];
    }

    /**
     * Generate unique document issue number: OUT-YYYYMM-XXXX.
     */
    public function generateIssueNumber(Carbon $date): string
    {
        $prefix = 'OUT-'.$date->format('Ym').'-';

        $latestIssue = GoodsIssue::where('issue_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($latestIssue && preg_match('/-(\d+)$/', $latestIssue->issue_number, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        }

        $number = $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        while (GoodsIssue::where('issue_number', $number)->exists()) {
            $seq++;
            $number = $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        }

        return $number;
    }
}
