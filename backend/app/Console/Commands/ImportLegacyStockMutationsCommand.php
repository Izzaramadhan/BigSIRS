<?php

namespace App\Console\Commands;

use App\Models\Logistics\StockMovement;
use App\Models\Logistics\StockMutation;
use App\Models\Logistics\StockMutationItem;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyStockMutationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:legacy-mutations {--dry-run : Only simulate import without modifying data} {--limit= : Limit the number of records to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import stock mutations from legacy database into new stock management schema';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($isDryRun) {
            $this->info('DRY RUN: No data will be modified.');
        }

        $this->info('Checking connection to legacy database...');

        try {
            $legacyQuery = DB::connection('legacy')->table('lgs_mutasi');
            $totalLegacy = $legacyQuery->count();
        } catch (\Throwable $e) {
            $this->error("Cannot connect to legacy database: {$e->getMessage()}");

            return 1;
        }

        $this->info("Found {$totalLegacy} mutations in legacy database.");

        if ($limit) {
            $legacyQuery->limit($limit);
            $this->info("Limiting import to {$limit} records.");
        }

        // Cache warehouses and medicines by legacy_id
        $warehousesMap = Warehouse::whereNotNull('legacy_id')->get()->keyBy('legacy_id');
        $medicinesMap = Medicine::with('unit')->whereNotNull('legacy_id')->get()->keyBy('legacy_id');
        $systemUser = User::first();
        $userId = $systemUser ? $systemUser->id : 1;

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $legacyMutations = $legacyQuery->orderBy('id')->get();

        foreach ($legacyMutations as $legacy) {
            // Check if already imported
            $existing = StockMutation::where('legacy_id', $legacy->id)->first();
            if ($existing) {
                $this->line("Mutation legacy ID {$legacy->id} already imported. Skipping.");
                $skipped++;

                continue;
            }

            // Map source and target warehouses without guessing or fallback
            $sourceWarehouse = $warehousesMap->get($legacy->id_gudang_asal);
            $targetWarehouse = $warehousesMap->get($legacy->id_gudang_tujuan);

            if (! $sourceWarehouse || ! $targetWarehouse) {
                $this->warn("Failed mapping warehouses for legacy mutation ID {$legacy->id}. Source ID: {$legacy->id_gudang_asal}, Target ID: {$legacy->id_gudang_tujuan}. Skipping.");
                $failed++;

                continue;
            }

            // Fetch legacy details
            $details = DB::connection('legacy')
                ->table('lgs_mutasi_detail')
                ->where('id_mutasi', $legacy->id)
                ->get();

            if ($details->isEmpty()) {
                $this->warn("Legacy mutation ID {$legacy->id} has no detail items. Skipping.");
                $skipped++;

                continue;
            }

            // Verify and map all items
            $mappedItems = [];
            $hasUnmappedItem = false;

            foreach ($details as $detail) {
                $fakturDetail = DB::connection('legacy')
                    ->table('lgs_faktur_detail')
                    ->where('id', $detail->id_lgs_faktur_detail)
                    ->first();

                if (! $fakturDetail) {
                    $this->warn("Legacy faktur detail ID {$detail->id_lgs_faktur_detail} not found for mutation ID {$legacy->id}.");
                    $hasUnmappedItem = true;
                    break;
                }

                $medicine = $medicinesMap->get($fakturDetail->id_obat);
                if (! $medicine) {
                    $this->warn("Legacy medicine ID {$fakturDetail->id_obat} not found in BigSIRS for mutation ID {$legacy->id}.");
                    $hasUnmappedItem = true;
                    break;
                }

                $batchNumber = ! empty($fakturDetail->batch) ? trim($fakturDetail->batch) : null;
                $expiredDate = ! empty($fakturDetail->ed) && $fakturDetail->ed !== '0000-00-00'
                    ? Carbon::parse($fakturDetail->ed)->format('Y-m-d')
                    : null;

                $mappedItems[] = [
                    'medicine' => $medicine,
                    'quantity' => (float) $detail->qty,
                    'batch_number' => $batchNumber,
                    'expired_date' => $expiredDate,
                ];
            }

            if ($hasUnmappedItem || empty($mappedItems)) {
                $this->warn("Mutation legacy ID {$legacy->id} has unmapped items and cannot be imported safely. Skipping.");
                $failed++;

                continue;
            }

            if ($isDryRun) {
                $this->info("DRY RUN: Would import mutation legacy ID {$legacy->id} with ".count($mappedItems).' items.');
                $created++;

                continue;
            }

            // Execute atomic creation in transaction
            try {
                DB::transaction(function () use ($legacy, $sourceWarehouse, $targetWarehouse, $mappedItems, $userId) {
                    $mutationDate = ! empty($legacy->tanggal) && $legacy->tanggal !== '0000-00-00'
                        ? Carbon::parse($legacy->tanggal)
                        : Carbon::parse($legacy->created_at);

                    $mutationNumber = 'MUT-LEGACY-'.str_pad((string) $legacy->id, 5, '0', STR_PAD_LEFT);

                    $mutation = StockMutation::create([
                        'legacy_id' => $legacy->id,
                        'mutation_number' => $mutationNumber,
                        'mutation_date' => $mutationDate,
                        'source_warehouse_id' => $sourceWarehouse->id,
                        'target_warehouse_id' => $targetWarehouse->id,
                        'notes' => ! empty($legacy->note) ? $legacy->note : 'Import legacy data',
                        'total_value' => 0.0,
                        'status' => 'validated',
                        'created_by' => $userId,
                        'validated_by' => $userId,
                        'validated_at' => $mutationDate,
                    ]);

                    $totalMutationValue = 0.0;

                    foreach ($mappedItems as $item) {
                        $medicine = $item['medicine'];
                        $qty = $item['quantity'];
                        $batch = $item['batch_number'];
                        $ed = $item['expired_date'];
                        $unitPrice = (float) ($medicine->selling_price_per_unit ?? 0);
                        $subtotal = $qty * $unitPrice;
                        $totalMutationValue += $subtotal;

                        // 1. Source warehouse stock (ensure non-negative stock tracking)
                        $sourceStock = WarehouseStock::firstOrCreate(
                            [
                                'warehouse_id' => $sourceWarehouse->id,
                                'medicine_id' => $medicine->id,
                                'batch_number' => $batch,
                                'expired_date' => $ed,
                            ],
                            ['quantity' => $qty]
                        );

                        $sourceBefore = (float) $sourceStock->quantity;
                        $sourceAfter = max(0.0, $sourceBefore - $qty);
                        $sourceStock->quantity = $sourceAfter;
                        $sourceStock->save();

                        // 2. Target warehouse stock
                        $targetStock = WarehouseStock::firstOrCreate(
                            [
                                'warehouse_id' => $targetWarehouse->id,
                                'medicine_id' => $medicine->id,
                                'batch_number' => $batch,
                                'expired_date' => $ed,
                            ],
                            ['quantity' => 0.0]
                        );

                        $targetBefore = (float) $targetStock->quantity;
                        $targetAfter = $targetBefore + $qty;
                        $targetStock->quantity = $targetAfter;
                        $targetStock->save();

                        // 3. Mutation item detail
                        $mutationItem = StockMutationItem::create([
                            'stock_mutation_id' => $mutation->id,
                            'medicine_id' => $medicine->id,
                            'medicine_name' => $medicine->name,
                            'quantity' => $qty,
                            'unit_price' => $unitPrice,
                            'subtotal' => $subtotal,
                            'unit' => $medicine->unit?->name ?? 'Unit',
                            'batch_number' => $batch,
                            'expired_date' => $ed,
                        ]);

                        // 4. Ledger OUT
                        StockMovement::create([
                            'warehouse_id' => $sourceWarehouse->id,
                            'medicine_id' => $medicine->id,
                            'stock_mutation_id' => $mutation->id,
                            'stock_mutation_item_id' => $mutationItem->id,
                            'movement_type' => 'mutation',
                            'direction' => 'OUT',
                            'quantity' => $qty,
                            'balance_before' => $sourceBefore,
                            'balance_after' => $sourceAfter,
                            'batch_number' => $batch,
                            'expired_date' => $ed,
                            'occurred_at' => $mutationDate,
                            'created_by' => $userId,
                        ]);

                        // 5. Ledger IN
                        StockMovement::create([
                            'warehouse_id' => $targetWarehouse->id,
                            'medicine_id' => $medicine->id,
                            'stock_mutation_id' => $mutation->id,
                            'stock_mutation_item_id' => $mutationItem->id,
                            'movement_type' => 'mutation',
                            'direction' => 'IN',
                            'quantity' => $qty,
                            'balance_before' => $targetBefore,
                            'balance_after' => $targetAfter,
                            'batch_number' => $batch,
                            'expired_date' => $ed,
                            'occurred_at' => $mutationDate,
                            'created_by' => $userId,
                        ]);
                    }

                    $mutation->total_value = $totalMutationValue;
                    $mutation->save();
                });

                $created++;
            } catch (\Throwable $e) {
                $this->error("Error importing legacy mutation ID {$legacy->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info('=== IMPORT SUMMARY ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Failed', $failed],
            ]
        );

        return 0;
    }
}
