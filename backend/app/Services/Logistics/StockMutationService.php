<?php

namespace App\Services\Logistics;

use App\Models\Logistics\StockMovement;
use App\Models\Logistics\StockMutation;
use App\Models\Logistics\StockMutationItem;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMutationService
{
    /**
     * Create and validate stock mutation atomically.
     *
     *
     * @throws ValidationException
     */
    public function createMutation(array $data, int $userId): StockMutation
    {
        return DB::transaction(function () use ($data, $userId) {
            $sourceWarehouseId = (int) ($data['source_warehouse_id'] ?? $data['sourceWarehouseId']);
            $targetWarehouseId = (int) ($data['target_warehouse_id'] ?? $data['targetWarehouseId']);

            if ($sourceWarehouseId === $targetWarehouseId) {
                throw ValidationException::withMessages([
                    'target_warehouse_id' => ['Gudang asal dan gudang tujuan tidak boleh sama.'],
                ]);
            }

            $sourceWarehouse = Warehouse::lockForUpdate()->find($sourceWarehouseId);
            $targetWarehouse = Warehouse::lockForUpdate()->find($targetWarehouseId);

            if (! $sourceWarehouse) {
                throw ValidationException::withMessages([
                    'source_warehouse_id' => ['Gudang asal tidak ditemukan.'],
                ]);
            }

            if (! $targetWarehouse) {
                throw ValidationException::withMessages([
                    'target_warehouse_id' => ['Gudang tujuan tidak ditemukan.'],
                ]);
            }

            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw ValidationException::withMessages([
                    'items' => ['Minimal harus terdapat satu item obat untuk dimutasi.'],
                ]);
            }

            // Check for duplicate medicines in payload
            $itemKeys = [];
            foreach ($itemsData as $index => $item) {
                $medId = (int) ($item['medicine_id'] ?? $item['medicineId'] ?? 0);
                $batch = trim($item['batch_number'] ?? $item['batchNumber'] ?? '');
                $key = "{$medId}#{$batch}";
                if (isset($itemKeys[$key])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.medicine_id" => ['Obat dengan batch yang sama tidak boleh dimasukkan lebih dari satu kali.'],
                    ]);
                }
                $itemKeys[$key] = true;
            }

            $mutationDate = ! empty($data['mutation_date'] ?? $data['mutationDate'] ?? null)
                ? Carbon::parse($data['mutation_date'] ?? $data['mutationDate'])
                : Carbon::now();

            $mutationNumber = $this->generateMutationNumber($mutationDate);

            // Create mutation header
            $mutation = StockMutation::create([
                'mutation_number' => $mutationNumber,
                'mutation_date' => $mutationDate,
                'source_warehouse_id' => $sourceWarehouseId,
                'target_warehouse_id' => $targetWarehouseId,
                'notes' => $data['notes'] ?? null,
                'total_value' => 0.0,
                'status' => 'validated',
                'created_by' => $userId,
                'validated_by' => $userId,
                'validated_at' => Carbon::now(),
            ]);

            $totalMutationValue = 0.0;

            // Process each item
            foreach ($itemsData as $index => $item) {
                $medicineId = (int) ($item['medicine_id'] ?? $item['medicineId']);
                $requestedQty = (float) ($item['quantity'] ?? 0);
                $specifiedBatch = isset($item['batch_number']) || isset($item['batchNumber'])
                    ? trim($item['batch_number'] ?? $item['batchNumber'] ?? '')
                    : null;

                if ($requestedQty <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => ['Kuantitas obat harus lebih besar dari nol.'],
                    ]);
                }

                $medicine = Medicine::with('unit')->find($medicineId);
                if (! $medicine || ! $medicine->is_active) {
                    throw ValidationException::withMessages([
                        "items.{$index}.medicine_id" => ['Obat yang dipilih tidak valid atau tidak aktif.'],
                    ]);
                }

                // Query stocks with lock
                $stocksQuery = WarehouseStock::where('warehouse_id', $sourceWarehouseId)
                    ->where('medicine_id', $medicineId)
                    ->where('quantity', '>', 0)
                    ->lockForUpdate();

                if (! empty($specifiedBatch)) {
                    $stocksQuery->where('batch_number', $specifiedBatch);
                }

                // FEFO allocation: earliest expired date first, nulls last
                $stocks = $stocksQuery
                    ->orderByRaw('CASE WHEN expired_date IS NULL THEN 1 ELSE 0 END, expired_date ASC, id ASC')
                    ->get();

                $totalAvailable = (float) $stocks->sum('quantity');

                if ($totalAvailable < $requestedQty) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => [
                            "Stok tidak mencukupi untuk {$medicine->name}. Stok tersedia di gudang asal: {$totalAvailable}, kuantitas diminta: {$requestedQty}.",
                        ],
                    ]);
                }

                $remainingToDeduct = $requestedQty;

                foreach ($stocks as $stockRecord) {
                    if ($remainingToDeduct <= 0) {
                        break;
                    }

                    $deductQty = min((float) $stockRecord->quantity, $remainingToDeduct);
                    $sourceBefore = (float) $stockRecord->quantity;
                    $sourceAfter = $sourceBefore - $deductQty;

                    if ($sourceAfter < 0) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" => ['Terjadi inkonsistensi saldo stok. Stok tidak boleh negatif.'],
                        ]);
                    }

                    $stockRecord->quantity = $sourceAfter;
                    $stockRecord->save();

                    // Find or create target warehouse stock with lock
                    $targetStock = WarehouseStock::where('warehouse_id', $targetWarehouseId)
                        ->where('medicine_id', $medicineId)
                        ->when($stockRecord->batch_number, fn ($q) => $q->where('batch_number', $stockRecord->batch_number), fn ($q) => $q->whereNull('batch_number'))
                        ->when($stockRecord->expired_date, fn ($q) => $q->where('expired_date', $stockRecord->expired_date), fn ($q) => $q->whereNull('expired_date'))
                        ->lockForUpdate()
                        ->first();

                    if (! $targetStock) {
                        $targetBefore = 0.0;
                        $targetAfter = $deductQty;
                        $targetStock = WarehouseStock::create([
                            'warehouse_id' => $targetWarehouseId,
                            'medicine_id' => $medicineId,
                            'quantity' => $targetAfter,
                            'batch_number' => $stockRecord->batch_number,
                            'expired_date' => $stockRecord->expired_date,
                        ]);
                    } else {
                        $targetBefore = (float) $targetStock->quantity;
                        $targetAfter = $targetBefore + $deductQty;
                        $targetStock->quantity = $targetAfter;
                        $targetStock->save();
                    }

                    $unitPrice = (float) ($medicine->selling_price_per_unit ?? 0);
                    $subtotal = $deductQty * $unitPrice;
                    $totalMutationValue += $subtotal;

                    // Create detail item snapshot
                    $mutationItem = StockMutationItem::create([
                        'stock_mutation_id' => $mutation->id,
                        'medicine_id' => $medicine->id,
                        'medicine_name' => $medicine->name,
                        'quantity' => $deductQty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'unit' => $medicine->unit?->name ?? 'Unit',
                        'batch_number' => $stockRecord->batch_number,
                        'expired_date' => $stockRecord->expired_date,
                    ]);

                    // Create OUT ledger for source warehouse
                    StockMovement::create([
                        'warehouse_id' => $sourceWarehouseId,
                        'medicine_id' => $medicine->id,
                        'stock_mutation_id' => $mutation->id,
                        'stock_mutation_item_id' => $mutationItem->id,
                        'movement_type' => 'mutation',
                        'direction' => 'OUT',
                        'quantity' => $deductQty,
                        'balance_before' => $sourceBefore,
                        'balance_after' => $sourceAfter,
                        'batch_number' => $stockRecord->batch_number,
                        'expired_date' => $stockRecord->expired_date,
                        'occurred_at' => $mutationDate,
                        'created_by' => $userId,
                    ]);

                    // Create IN ledger for target warehouse
                    StockMovement::create([
                        'warehouse_id' => $targetWarehouseId,
                        'medicine_id' => $medicine->id,
                        'stock_mutation_id' => $mutation->id,
                        'stock_mutation_item_id' => $mutationItem->id,
                        'movement_type' => 'mutation',
                        'direction' => 'IN',
                        'quantity' => $deductQty,
                        'balance_before' => $targetBefore,
                        'balance_after' => $targetAfter,
                        'batch_number' => $stockRecord->batch_number,
                        'expired_date' => $stockRecord->expired_date,
                        'occurred_at' => $mutationDate,
                        'created_by' => $userId,
                    ]);

                    $remainingToDeduct -= $deductQty;
                }
            }

            $mutation->total_value = $totalMutationValue;
            $mutation->save();

            return $mutation->load(['sourceWarehouse', 'targetWarehouse', 'creator', 'validator', 'items.medicine.unit']);
        });
    }

    /**
     * Generate unique mutation number format: MUT-YYYYMM-####.
     */
    protected function generateMutationNumber(Carbon $date): string
    {
        $prefix = 'MUT-'.$date->format('Ym').'-';

        $latestMutation = StockMutation::where('mutation_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($latestMutation && preg_match('/-(\d+)$/', $latestMutation->mutation_number, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        }

        $number = $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        // Safety check to ensure uniqueness
        while (StockMutation::where('mutation_number', $number)->exists()) {
            $seq++;
            $number = $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        }

        return $number;
    }
}
