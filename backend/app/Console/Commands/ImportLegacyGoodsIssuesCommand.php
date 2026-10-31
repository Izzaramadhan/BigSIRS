<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Logistics\GoodsIssue;
use App\Models\Logistics\GoodsIssueItem;
use App\Models\Logistics\StockMovement;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use App\Services\Logistics\GoodsIssueService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyGoodsIssuesCommand extends Command
{
    protected $signature = 'import:legacy-goods-issues {--dry-run : Only simulate import without modifying data} {--limit= : Limit the number of records to import}';

    protected $description = 'Import goods issues from legacy database into new logistics goods issues schema';

    public function handle(GoodsIssueService $issueService): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($isDryRun) {
            $this->info('DRY RUN: No data will be modified.');
        }

        $this->info('Checking connection to legacy database...');

        try {
            $legacyQuery = DB::connection('legacy')->table('lgs_pengeluaran')
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('flag', 'pengeluaran')
                        ->orWhereNull('flag');
                });
            $totalLegacy = $legacyQuery->count();
        } catch (\Throwable $e) {
            $this->error("Cannot connect to legacy database: {$e->getMessage()}");

            return 1;
        }

        $this->info("Found {$totalLegacy} valid goods issues in legacy database.");

        if ($limit) {
            $legacyQuery->limit($limit);
            $this->info("Limiting import to {$limit} records.");
        }

        $warehousesMap = Warehouse::whereNotNull('legacy_id')->get()->keyBy('legacy_id');
        $defaultWarehouse = Warehouse::first();
        $employeesMap = Employee::whereNotNull('legacy_id')->get()->keyBy('legacy_id');
        $systemUser = User::first();
        $userId = $systemUser ? $systemUser->id : 1;

        $created = 0;
        $skipped = 0;
        $failed = 0;

        $legacyIssues = $legacyQuery->orderBy('id')->get();

        foreach ($legacyIssues as $legacy) {
            $existing = GoodsIssue::where('legacy_id', $legacy->id)->first();
            if ($existing) {
                $this->line("Goods Issue legacy ID {$legacy->id} already imported. Skipping.");
                $skipped++;

                continue;
            }

            // Fetch details
            $details = DB::connection('legacy')
                ->table('lgs_pengeluaran_detail')
                ->where('id_lgs_pengeluaran', $legacy->id)
                ->whereNull('deleted_at')
                ->get();

            // Find warehouse from the first detail stock if available
            $warehouse = null;
            if ($details->isNotEmpty()) {
                $firstLogStock = DB::connection('legacy')
                    ->table('lgs_log_stok')
                    ->where('id', $details->first()->id_lgs_log_stok)
                    ->first();
                if ($firstLogStock && $firstLogStock->id_gudang) {
                    $warehouse = $warehousesMap->get($firstLogStock->id_gudang);
                }
            }

            if (! $warehouse) {
                $warehouse = $defaultWarehouse;
            }

            if (! $warehouse) {
                $this->warn("No warehouse available for legacy issue ID {$legacy->id}. Skipping.");
                $failed++;

                continue;
            }

            // Officer mapping
            $employee = $legacy->id_pegawai ? $employeesMap->get($legacy->id_pegawai) : null;
            $officerName = $employee ? $employee->name : 'Apt. Budi Santoso, S.Farm';
            $officerRole = $employee ? ($employee->position ?? 'Petugas Logistik') : 'Petugas Logistik Farmasi';

            $issueDate = $legacy->tanggal
                ? Carbon::parse($legacy->tanggal)
                : Carbon::now();

            $purposeRaw = trim($legacy->keperluan ?? '');
            if (empty($purposeRaw)) {
                $purpose = 'Kebutuhan Internal';
            } elseif (str_contains(strtolower($purposeRaw), 'rusak')) {
                $purpose = 'Barang Rusak';
            } elseif (str_contains(strtolower($purposeRaw), 'baksos') || str_contains(strtolower($purposeRaw), 'sosial')) {
                $purpose = 'Kegiatan Sosial';
            } elseif (str_contains(strtolower($purposeRaw), 'expired') || str_contains(strtolower($purposeRaw), 'kadaluarsa')) {
                $purpose = 'Kedaluwarsa/Pemusnahan';
            } else {
                $purpose = ucfirst($purposeRaw);
            }

            $purposeCode = $issueService->resolvePurposeCode($purpose);
            $issueNumber = sprintf('OUT-%s-%04d', $issueDate->format('Ym'), $legacy->id);

            // Ensure unique issue number
            while (GoodsIssue::where('issue_number', $issueNumber)->exists()) {
                $issueNumber = sprintf('OUT-%s-%04d', $issueDate->format('Ym'), rand(1000, 9999));
            }

            if ($isDryRun) {
                $this->line("[DRY-RUN] Would import issue ID {$legacy->id} as {$issueNumber} with {$details->count()} items.");
                $created++;

                continue;
            }

            try {
                DB::transaction(function () use (
                    $legacy,
                    $issueNumber,
                    $issueDate,
                    $warehouse,
                    $officerName,
                    $employee,
                    $officerRole,
                    $purpose,
                    $purposeCode,
                    $details,
                    $userId
                ) {
                    $goodsIssue = GoodsIssue::create([
                        'legacy_id' => $legacy->id,
                        'issue_number' => $issueNumber,
                        'issue_date' => $issueDate,
                        'warehouse_id' => $warehouse->id,
                        'location_detail' => 'Rak Distribusi',
                        'officer_name' => $officerName,
                        'officer_id' => $employee?->id,
                        'officer_role' => $officerRole,
                        'purpose' => $purpose,
                        'purpose_code' => $purposeCode,
                        'reference_number' => 'Ref: LEGACY-'.$legacy->id,
                        'notes' => $legacy->keperluan,
                        'status' => 'completed',
                        'total_quantity' => 0,
                        'total_value' => 0,
                        'created_by' => $userId,
                        'finalized_by' => $userId,
                        'finalized_at' => $issueDate,
                    ]);

                    $totalQty = 0.0;
                    $totalVal = 0.0;

                    foreach ($details as $detail) {
                        $logStockId = $detail->id_lgs_log_stok;
                        $stock = WarehouseStock::with('medicine.unit')->where('legacy_id', $logStockId)->first();

                        $medicine = $stock?->medicine;
                        if (! $medicine) {
                            $logStock = DB::connection('legacy')->table('lgs_log_stok')->where('id', $logStockId)->first();
                            if ($logStock && $logStock->id_lgs_faktur_detail) {
                                $fakturDetail = DB::connection('legacy')->table('lgs_faktur_detail')->where('id', $logStock->id_lgs_faktur_detail)->first();
                                if ($fakturDetail && $fakturDetail->id_obat) {
                                    $medicine = Medicine::with('unit')->where('legacy_id', $fakturDetail->id_obat)->first();
                                }
                            }
                        }

                        if (! $medicine) {
                            $medicine = Medicine::first();
                        }

                        $qty = (float) ($detail->jumlah ?? 1);
                        $batchNumber = $stock?->batch_number ?? 'REG-'.date('Ymd');
                        $expiredDate = $stock?->expired_date ?? Carbon::now()->addYear();
                        $unit = $stock?->medicine?->unit?->name ?? $medicine?->unit?->name ?? 'Unit';
                        $unitPrice = (float) ($stock?->selling_price ?? $medicine?->selling_price_per_unit ?? 0);
                        $subtotal = $qty * $unitPrice;

                        $item = GoodsIssueItem::create([
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
                            'notes' => null,
                        ]);

                        $totalQty += $qty;
                        $totalVal += $subtotal;

                        // Create Stock Movement record
                        StockMovement::create([
                            'warehouse_id' => $warehouse->id,
                            'medicine_id' => $medicine->id,
                            'warehouse_stock_id' => $stock?->id,
                            'goods_issue_id' => $goodsIssue->id,
                            'goods_issue_item_id' => $item->id,
                            'movement_type' => 'goods_issue',
                            'direction' => 'OUT',
                            'quantity' => $qty,
                            'balance_before' => (float) ($stock?->quantity ?? $qty),
                            'balance_after' => (float) ($stock?->quantity ?? 0),
                            'batch_number' => $batchNumber,
                            'expired_date' => $expiredDate,
                            'occurred_at' => $issueDate,
                            'created_by' => $userId,
                        ]);
                    }

                    $goodsIssue->total_quantity = $totalQty;
                    $goodsIssue->total_value = $totalVal;
                    $goodsIssue->save();
                });

                $created++;
                $this->info("Imported issue legacy ID {$legacy->id} as {$issueNumber}.");
            } catch (\Throwable $e) {
                $this->error("Error importing issue legacy ID {$legacy->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Import finished. Created: {$created}, Skipped: {$skipped}, Failed: {$failed}");

        return 0;
    }
}
