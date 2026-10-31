<?php

namespace App\Console\Commands;

use App\Models\Logistics\PosSale;
use App\Models\Logistics\PosSaleItem;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyPosSalesCommand extends Command
{
    protected $signature = 'import:legacy-pos-sales {--dry-run : Only simulate import}';

    protected $description = 'Import POS OTC sales from legacy database into new pos_sales and pos_sale_items tables';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->info('DRY RUN: No data will be modified.');
        }

        try {
            $legacyQuery = DB::connection('legacy')->table('lgs_pengeluaran')
                ->where('flag', 'apotek_bebas')
                ->whereNull('deleted_at');
            $totalLegacy = $legacyQuery->count();
        } catch (\Throwable $e) {
            $this->error("Cannot connect to legacy database: {$e->getMessage()}");

            return 1;
        }

        $this->info("Found {$totalLegacy} POS OTC sales in legacy database.");

        $warehousesMap = Warehouse::whereNotNull('legacy_id')->get()->keyBy('legacy_id');
        $defaultWarehouse = Warehouse::where('code', 'AP-RJ')->first() ?? Warehouse::first();
        $medicinesMap = Medicine::whereNotNull('legacy_id')->get()->keyBy('legacy_id');
        $systemUser = User::first();
        $userId = $systemUser ? $systemUser->id : 1;
        $userName = $systemUser ? $systemUser->name : 'Kasir Farmasi';

        $created = 0;
        $skipped = 0;

        $legacySales = $legacyQuery->orderBy('id')->get();

        foreach ($legacySales as $legacy) {
            $existing = PosSale::where('legacy_id', $legacy->id)->first();
            if ($existing) {
                $this->line("POS Sale legacy ID {$legacy->id} already imported. Skipping.");
                $skipped++;

                continue;
            }

            // Fetch details
            $details = DB::connection('legacy')
                ->table('lgs_pengeluaran_detail')
                ->where('id_lgs_pengeluaran', $legacy->id)
                ->whereNull('deleted_at')
                ->get();

            $warehouse = null;
            $itemsData = [];
            $totalQty = 0;
            $totalAmount = 0;

            foreach ($details as $detail) {
                $logStock = DB::connection('legacy')->table('lgs_log_stok')->where('id', $detail->id_lgs_log_stok)->first();
                $fakturDetail = null;
                $batchNumber = null;
                $expiredDate = null;
                $price = 0;
                $medicine = null;

                if ($logStock) {
                    if (! $warehouse && $logStock->id_gudang) {
                        $warehouse = $warehousesMap->get($logStock->id_gudang);
                    }
                    $price = (float) ($logStock->harga_jual ?? 0);

                    if ($logStock->id_lgs_faktur_detail) {
                        $fakturDetail = DB::connection('legacy')->table('lgs_faktur_detail')->where('id', $logStock->id_lgs_faktur_detail)->first();
                        if ($fakturDetail) {
                            $batchNumber = $fakturDetail->batch ?? null;
                            $expiredDate = $fakturDetail->ed ?? null;
                            $obatId = $fakturDetail->id_obat ?? null;
                            if ($obatId) {
                                $medicine = $medicinesMap->get($obatId);
                            }
                        }
                    }
                }

                if (! $medicine) {
                    // Try to find any medicine or use first medicine
                    $medicine = Medicine::first();
                }

                $qty = (float) $detail->jumlah;
                $itemSubtotal = $price * $qty;
                $totalQty += $qty;
                $totalAmount += $itemSubtotal;

                $parsedExpDate = null;
                if (! empty($expiredDate) && $expiredDate !== '0000-00-00') {
                    try {
                        $parsed = Carbon::parse($expiredDate);
                        if ($parsed->year >= 1970 && $parsed->year <= 2100) {
                            $parsedExpDate = $parsed->format('Y-m-d');
                        }
                    } catch (\Throwable $e) {
                        // ignore invalid date
                    }
                }

                $itemsData[] = [
                    'medicine_id' => $medicine?->id ?? 1,
                    'medicine_name' => $medicine?->name ?? 'Obat Bebas',
                    'batch_number' => $batchNumber ?? 'BATCH-LEGACY',
                    'expired_date' => $parsedExpDate,
                    'unit' => $medicine?->unit?->name ?? 'Pcs',
                    'quantity' => $qty,
                    'price' => $price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            if (! $warehouse) {
                $warehouse = $defaultWarehouse;
            }

            $date = Carbon::parse($legacy->created_at ?? $legacy->tanggal ?? now());
            $datePrefix = $date->format('Ymd');
            $seq = str_pad($legacy->id, 4, '0', STR_PAD_LEFT);
            $txNumber = "TR-OTC-{$datePrefix}-{$seq}";

            $customerName = ! empty(trim($legacy->pembeli ?? '')) ? trim($legacy->pembeli) : 'Walk-in Customer';
            $customerType = 'Umum';
            $paymentMethod = ! empty(trim($legacy->cara_bayar ?? '')) ? trim($legacy->cara_bayar) : 'Tunai';

            // Status: 1 = completed, 0 = cancelled
            $status = ($legacy->status == 1) ? 'completed' : 'cancelled';

            if ($isDryRun) {
                $this->line("[DRY RUN] Would import sale: {$txNumber} ({$customerName}) with " . count($itemsData) . " items. Status: {$status}");
                $created++;

                continue;
            }

            DB::transaction(function () use (
                $legacy,
                $txNumber,
                $date,
                $warehouse,
                $customerName,
                $customerType,
                $userName,
                $userId,
                $paymentMethod,
                $totalQty,
                $totalAmount,
                $status,
                $itemsData
            ) {
                $posSale = PosSale::create([
                    'legacy_id' => $legacy->id,
                    'transaction_number' => $txNumber,
                    'transaction_date' => $date,
                    'warehouse_id' => $warehouse->id,
                    'customer_name' => $customerName,
                    'customer_type' => $customerType,
                    'cashier_name' => $userName,
                    'cashier_id' => $userId,
                    'payment_method' => $paymentMethod,
                    'total_quantity' => $totalQty,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $totalAmount,
                    'change_amount' => 0,
                    'status' => $status,
                    'notes' => $legacy->keperluan ?? null,
                    'completed_at' => $status === 'completed' ? $date : null,
                    'cancelled_at' => $status === 'cancelled' ? $date : null,
                    'cancellation_reason' => $status === 'cancelled' ? 'Dibatalkan pada sistem legacy' : null,
                ]);

                foreach ($itemsData as $it) {
                    $posSale->items()->create($it);
                }
            });

            $this->info("Imported POS sale {$txNumber} (ID: {$legacy->id}) with " . count($itemsData) . " items.");
            $created++;
        }

        $this->info("Import finished. Created: {$created}, Skipped: {$skipped}");

        return 0;
    }
}
