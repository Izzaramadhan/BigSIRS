<?php

namespace App\Console\Commands;

use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyWarehouseStocksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-warehouse-stocks {--dry-run : Only simulate import without modifying data} {--limit= : Limit the number of records to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import warehouse stocks and inventory lots from legacy lgs_log_stok into BigSIRS schema';

    /**
     * Command aliases.
     *
     * @var array<string>
     */
    protected $aliases = ['import:legacy-warehouse-stocks'];

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
            $legacyQuery = DB::connection('legacy')->table('lgs_log_stok as ls')
                ->join('lgs_faktur_detail as fd', 'ls.id_lgs_faktur_detail', '=', 'fd.id')
                ->where('ls.id_gudang', '>', 0)
                ->whereNull('ls.deleted_at')
                ->whereNull('fd.deleted_at')
                ->select(
                    'ls.id as legacy_log_id',
                    'ls.id_gudang',
                    'fd.id_obat',
                    'ls.qty',
                    'ls.harga_jual',
                    'fd.batch',
                    'fd.ed'
                );

            $totalLegacy = $legacyQuery->count();
        } catch (\Throwable $e) {
            $this->error("Tidak dapat terhubung ke database legacy: {$e->getMessage()}");

            return 1;
        }

        $this->info("Ditemukan {$totalLegacy} data saldo stok aktif pada database legacy.");

        if ($limit) {
            $legacyQuery->limit($limit);
            $this->info("Membatasi proses import hingga {$limit} data.");
        }

        $warehousesMap = Warehouse::whereNotNull('legacy_id')->get()->keyBy('legacy_id');
        $medicinesMap = Medicine::whereNotNull('legacy_id')->get()->keyBy('legacy_id');

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $legacyRows = $legacyQuery->orderBy('ls.id')->get();

        foreach ($legacyRows as $legacy) {
            // Resolve warehouse
            $warehouse = $warehousesMap->get($legacy->id_gudang) ?? Warehouse::find($legacy->id_gudang);
            if (! $warehouse) {
                $this->warn("Gudang legacy ID {$legacy->id_gudang} tidak ditemukan. Dilewati.");
                $skipped++;

                continue;
            }

            // Resolve medicine
            $medicine = $medicinesMap->get($legacy->id_obat) ?? Medicine::find($legacy->id_obat);
            if (! $medicine) {
                $this->warn("Obat legacy ID {$legacy->id_obat} tidak ditemukan. Dilewati.");
                $skipped++;

                continue;
            }

            // Format date
            $expDate = null;
            if (! empty($legacy->ed) && $legacy->ed !== '0000-00-00') {
                try {
                    $expDate = Carbon::parse($legacy->ed)->format('Y-m-d');
                } catch (\Throwable) {
                    $expDate = null;
                }
            }

            $batchNumber = ! empty($legacy->batch) ? trim($legacy->batch) : null;
            $quantity = (float) ($legacy->qty ?? 0);
            $sellingPrice = (float) ($legacy->harga_jual ?? 0);

            if ($isDryRun) {
                $existing = WarehouseStock::where('legacy_id', $legacy->legacy_log_id)->first();
                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }

                continue;
            }

            try {
                $stock = WarehouseStock::updateOrCreate(
                    ['legacy_id' => $legacy->legacy_log_id],
                    [
                        'warehouse_id' => $warehouse->id,
                        'medicine_id' => $medicine->id,
                        'quantity' => $quantity,
                        'selling_price' => $sellingPrice,
                        'batch_number' => $batchNumber,
                        'expired_date' => $expDate,
                    ]
                );

                if ($stock->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Gagal mengimpor log stok ID {$legacy->legacy_log_id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('=== HASIL IMPORT SALDO STOK WAREHOUSE ===');
        $this->line("Legacy ditemukan : {$totalLegacy}");
        $this->line("Dibuat           : {$created}");
        $this->line("Diperbarui       : {$updated}");
        $this->line("Dilewati         : {$skipped}");
        $this->line("Gagal            : {$failed}");
        $this->line('Total stok aktif : '.WarehouseStock::count());

        return $failed > 0 ? 1 : 0;
    }
}

