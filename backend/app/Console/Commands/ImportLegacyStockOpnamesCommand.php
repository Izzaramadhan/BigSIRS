<?php

namespace App\Console\Commands;

use App\Models\Logistics\StockOpname;
use App\Models\Logistics\StockOpnameItem;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyStockOpnamesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-stock-opnames {--dry-run : Only simulate import without modifying data} {--limit= : Limit the number of records to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import stock opnames from legacy database into new stock management schema';

    /**
     * Aliases for the command.
     *
     * @var array<string>
     */
    protected $aliases = ['import:legacy-stock-opnames'];

    /**
     * Status map from legacy integer to new enum string.
     */
    protected array $statusMap = [
        0 => StockOpname::STATUS_DRAFT,
        1 => StockOpname::STATUS_COUNTING,
        2 => StockOpname::STATUS_REVIEW,
        3 => StockOpname::STATUS_COMPLETED,
        4 => StockOpname::STATUS_CANCELLED,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($isDryRun) {
            $this->warn('Mode DRY-RUN aktif: Simulasi import tanpa mengubah database.');
        }

        $this->info('Memeriksa koneksi database legacy...');

        try {
            $legacyQuery = DB::connection('legacy')->table('lgs_stok_opname');
            $totalLegacy = $legacyQuery->count();
        } catch (\Throwable $e) {
            $this->error("Tidak dapat terhubung ke database legacy: {$e->getMessage()}");

            return 1;
        }

        $this->info("Ditemukan {$totalLegacy} data stok opname pada database legacy.");

        if ($limit) {
            $legacyQuery->limit($limit);
            $this->info("Membatasi proses import hingga {$limit} data.");
        }

        // Cache warehouses by legacy_id
        $warehousesMap = Warehouse::whereNotNull('legacy_id')->get()->keyBy('legacy_id');
        $medicinesMap = Medicine::with('unit')->whereNotNull('legacy_id')->get()->keyBy('legacy_id');
        $systemUser = User::first();
        $userId = $systemUser ? $systemUser->id : 1;

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $legacyOpnames = $legacyQuery->orderBy('id')->get();

        foreach ($legacyOpnames as $legacy) {
            // 1. Map Warehouse
            $warehouse = $warehousesMap->get($legacy->id_gudang);
            if (! $warehouse) {
                // Fallback attempt: match by warehouse id directly
                $warehouse = Warehouse::find($legacy->id_gudang);
            }

            if (! $warehouse) {
                $this->warn("Gagal mapping gudang untuk legacy ID {$legacy->id} (id_gudang: {$legacy->id_gudang}). Data dilewati.");
                $failed++;

                continue;
            }

            // 2. Map Status
            $mappedStatus = $this->statusMap[(int) $legacy->status] ?? StockOpname::STATUS_DRAFT;

            // 3. Format Date & Opname Number
            $startDate = ! empty($legacy->tanggal) && $legacy->tanggal !== '0000-00-00'
                ? Carbon::parse($legacy->tanggal)
                : Carbon::parse($legacy->created_at);

            $opnameNumber = 'SO-'.$startDate->format('Ym').'-'.str_pad((string) $legacy->id, 4, '0', STR_PAD_LEFT);

            // 4. Fetch legacy details
            $legacyDetails = DB::connection('legacy')
                ->table('lgs_stok_opname_detail')
                ->where('id_lgs_stok_opname', $legacy->id)
                ->get();

            $mappedItems = [];
            $hasUnmappedItem = false;

            foreach ($legacyDetails as $detail) {
                $logStok = DB::connection('legacy')
                    ->table('lgs_log_stok')
                    ->where('id', $detail->id_lgs_log_stok)
                    ->first();

                if (! $logStok) {
                    $this->warn("Log stok ID {$detail->id_lgs_log_stok} tidak ditemukan untuk legacy opname ID {$legacy->id}.");
                    $hasUnmappedItem = true;
                    break;
                }

                $fakturDetail = DB::connection('legacy')
                    ->table('lgs_faktur_detail')
                    ->where('id', $logStok->id_lgs_faktur_detail)
                    ->first();

                if (! $fakturDetail) {
                    $this->warn("Faktur detail ID {$logStok->id_lgs_faktur_detail} tidak ditemukan untuk log stok ID {$logStok->id}.");
                    $hasUnmappedItem = true;
                    break;
                }

                $medicine = $medicinesMap->get($fakturDetail->id_obat);
                if (! $medicine) {
                    $this->warn("Obat legacy ID {$fakturDetail->id_obat} tidak ditemukan di sistem baru.");
                    $hasUnmappedItem = true;
                    break;
                }

                $batchNumber = ! empty($fakturDetail->batch) ? trim($fakturDetail->batch) : null;
                $expiredDate = ! empty($fakturDetail->ed) && $fakturDetail->ed !== '0000-00-00'
                    ? Carbon::parse($fakturDetail->ed)->format('Y-m-d')
                    : null;

                $sysStock = (float) $detail->stok_akhir;
                $physStock = $detail->fisik !== null ? (float) $detail->fisik : null;
                $diff = $physStock !== null ? $physStock - $sysStock : null;
                $unitPrice = (float) ($logStok->harga_jual ?? $medicine->selling_price_per_unit ?? 1000);
                $diffVal = $diff !== null ? $diff * $unitPrice : null;

                // Find matching warehouse stock id if available
                $whStock = WarehouseStock::where('warehouse_id', $warehouse->id)
                    ->where('medicine_id', $medicine->id)
                    ->first();

                $mappedItems[] = [
                    'warehouse_stock_id' => $whStock?->id,
                    'medicine_id' => $medicine->id,
                    'medicine_name' => $medicine->name,
                    'medicine_code' => $medicine->code,
                    'unit' => $medicine->unit?->name ?? 'Unit',
                    'batch_number' => $batchNumber,
                    'expired_date' => $expiredDate,
                    'system_stock' => $sysStock,
                    'physical_stock' => $physStock,
                    'difference' => $diff,
                    'unit_price' => $unitPrice,
                    'difference_value' => $diffVal,
                    'notes' => $diff != 0 ? "Selisih fisik {$diff} {$medicine->unit?->name}" : 'Sesuai fisik',
                ];
            }

            if ($hasUnmappedItem) {
                $failed++;

                continue;
            }

            // Check if existing record by legacy_id or opname_number
            $existing = StockOpname::where('legacy_id', $legacy->id)
                ->orWhere('opname_number', $opnameNumber)
                ->first();

            if ($isDryRun) {
                if ($existing) {
                    $this->line("DRY RUN: Akan memperbarui sesi stok opname legacy ID {$legacy->id} ({$opnameNumber}).");
                    $updated++;
                } else {
                    $this->line("DRY RUN: Akan membuat sesi stok opname baru dari legacy ID {$legacy->id} ({$opnameNumber}).");
                    $created++;
                }

                continue;
            }

            // Execute atomic updateOrCreate in database transaction
            try {
                DB::transaction(function () use (
                    $legacy,
                    $existing,
                    $warehouse,
                    $mappedStatus,
                    $startDate,
                    $opnameNumber,
                    $mappedItems,
                    $userId,
                    &$created,
                    &$updated
                ) {
                    $diffCount = 0;
                    $totalDiffVal = 0.0;
                    $countedItems = 0;

                    foreach ($mappedItems as $item) {
                        if ($item['physical_stock'] !== null) {
                            $countedItems++;
                            if ($item['difference'] != 0) {
                                $diffCount++;
                                $totalDiffVal += (float) ($item['difference_value'] ?? 0);
                            }
                        }
                    }

                    $officerName = 'dr. FIRDAUS DJUNID, Sp. A';
                    $officerRole = 'Kepala Instalasi Farmasi';

                    $title = match ($mappedStatus) {
                        StockOpname::STATUS_DRAFT => "Draft Audit Persediaan {$warehouse->name}",
                        StockOpname::STATUS_COUNTING => "Audit Fisik Sediaan {$warehouse->name}",
                        StockOpname::STATUS_REVIEW => "Audit Bulanan {$warehouse->name} (Peninjauan)",
                        StockOpname::STATUS_COMPLETED => "Audit Selesai {$warehouse->name}",
                        StockOpname::STATUS_CANCELLED => "Audit Khusus {$warehouse->name} (Dibatalkan)",
                    };

                    $submittedBy = in_array($mappedStatus, [StockOpname::STATUS_REVIEW, StockOpname::STATUS_COMPLETED], true) ? $userId : null;
                    $submittedAt = in_array($mappedStatus, [StockOpname::STATUS_REVIEW, StockOpname::STATUS_COMPLETED], true) ? $startDate->copy()->addHours(4) : null;
                    $approvedBy = $mappedStatus === StockOpname::STATUS_COMPLETED ? $userId : null;
                    $approvedAt = $mappedStatus === StockOpname::STATUS_COMPLETED ? $startDate->copy()->addHours(6) : null;
                    $cancelledBy = $mappedStatus === StockOpname::STATUS_CANCELLED ? $userId : null;
                    $cancelledAt = $mappedStatus === StockOpname::STATUS_CANCELLED ? $startDate->copy()->addHours(2) : null;

                    $opnameData = [
                        'legacy_id' => $legacy->id,
                        'opname_number' => $opnameNumber,
                        'warehouse_id' => $warehouse->id,
                        'title' => $title,
                        'type' => 'Audit Rutin',
                        'status' => $mappedStatus,
                        'start_date' => $startDate,
                        'end_date' => $approvedAt ?? $cancelledAt,
                        'location_detail' => "Area Depo {$warehouse->name}",
                        'officer_name' => $officerName,
                        'officer_role' => $officerRole,
                        'notes' => "Data stok opname migrasi legacy ID {$legacy->id}",
                        'total_items' => count($mappedItems),
                        'counted_items' => $countedItems,
                        'difference_count' => $diffCount,
                        'total_difference_value' => $totalDiffVal,
                        'created_by' => $userId,
                        'submitted_by' => $submittedBy,
                        'submitted_at' => $submittedAt,
                        'approved_by' => $approvedBy,
                        'approved_at' => $approvedAt,
                        'cancelled_by' => $cancelledBy,
                        'cancelled_at' => $cancelledAt,
                        'cancel_reason' => $mappedStatus === StockOpname::STATUS_CANCELLED ? 'Dibatalkan pada sistem legacy.' : null,
                    ];

                    if ($existing) {
                        $existing->update($opnameData);
                        $opname = $existing;
                        $updated++;
                    } else {
                        $opname = StockOpname::create($opnameData);
                        $created++;
                    }

                    // Sync items
                    $opname->items()->delete();
                    foreach ($mappedItems as $item) {
                        StockOpnameItem::create([
                            'stock_opname_id' => $opname->id,
                            'warehouse_stock_id' => $item['warehouse_stock_id'],
                            'medicine_id' => $item['medicine_id'],
                            'medicine_name' => $item['medicine_name'],
                            'medicine_code' => $item['medicine_code'],
                            'unit' => $item['unit'],
                            'batch_number' => $item['batch_number'],
                            'expired_date' => $item['expired_date'],
                            'system_stock' => $item['system_stock'],
                            'physical_stock' => $item['physical_stock'],
                            'difference' => $item['difference'],
                            'unit_price' => $item['unit_price'],
                            'difference_value' => $item['difference_value'],
                            'notes' => $item['notes'],
                            'counted_by' => $item['physical_stock'] !== null ? $userId : null,
                            'counted_at' => $item['physical_stock'] !== null ? $startDate->copy()->addHours(2) : null,
                        ]);
                    }
                });
            } catch (\Throwable $e) {
                $this->error("Gagal mengimpor stok opname legacy ID {$legacy->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info('=== HASIL IMPORT STOK OPNAME LEGACY ===');
        $this->line("Legacy ditemukan : {$totalLegacy}");
        $this->line("Dibuat            : {$created}");
        $this->line("Diperbarui        : {$updated}");
        $this->line("Dilewati          : {$skipped}");
        $this->line("Gagal             : {$failed}");
        $this->line('Total target      : '.($created + $updated));

        return 0;
    }
}
