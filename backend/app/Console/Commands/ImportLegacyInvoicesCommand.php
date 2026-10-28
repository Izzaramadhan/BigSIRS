<?php

namespace App\Console\Commands;

use App\Models\Logistics\Invoice;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Supplier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:legacy-invoices {--dry-run : Only show what would be done without making changes} {--limit= : Limit the number of records to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import invoices and invoice details from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($isDryRun) {
            $this->info('DRY RUN: No data will be modified.');
        }

        $this->info('Starting import of legacy invoices...');

        try {
            $legacyQuery = DB::connection('legacy')->table('lgs_faktur');
            $totalLegacy = $legacyQuery->count();
        } catch (\Throwable $e) {
            $this->error("Cannot connect to legacy database: {$e->getMessage()}");

            return 1;
        }

        $this->info("Found {$totalLegacy} invoices in legacy database.");

        if ($limit) {
            $legacyQuery->limit($limit);
            $this->info("Limiting import to {$limit} invoices.");
        }

        // Cache suppliers and medicines by legacy_id
        $suppliersMap = Supplier::whereNotNull('legacy_id')
            ->get()
            ->keyBy('legacy_id');

        $medicinesMap = Medicine::with('unit')
            ->whereNotNull('legacy_id')
            ->get()
            ->keyBy('legacy_id');

        $defaultSupplier = Supplier::first();
        $defaultMedicine = Medicine::first();

        $inserted = 0;
        $updated = 0;
        $failed = 0;

        $legacyQuery->orderBy('id')->chunk(200, function ($fakturs) use (
            $isDryRun, $suppliersMap, $medicinesMap, $defaultSupplier, $defaultMedicine, &$inserted, &$updated, &$failed
        ) {
            $fakturIds = $fakturs->pluck('id')->toArray();
            $details = DB::connection('legacy')
                ->table('lgs_faktur_detail')
                ->whereIn('id_lgs_faktur', $fakturIds)
                ->get()
                ->groupBy('id_lgs_faktur');

            foreach ($fakturs as $legacy) {
                try {
                    $invoiceNumber = trim($legacy->nomor);
                    if (empty($invoiceNumber)) {
                        $invoiceNumber = 'FKT-LEGACY-'.$legacy->id;
                    }

                    // Format dates
                    $invoiceDate = null;
                    if (! empty($legacy->tanggal) && $legacy->tanggal !== '0000-00-00') {
                        try {
                            $invoiceDate = Carbon::parse($legacy->tanggal)->toDateString();
                        } catch (\Throwable) {
                            $invoiceDate = Carbon::today()->toDateString();
                        }
                    } else {
                        $invoiceDate = Carbon::today()->toDateString();
                    }

                    $dueDate = null;
                    if (! empty($legacy->jatuh_tempo) && $legacy->jatuh_tempo !== '0000-00-00') {
                        try {
                            $dueDate = Carbon::parse($legacy->jatuh_tempo)->toDateString();
                        } catch (\Throwable) {
                            $dueDate = Carbon::parse($invoiceDate)->addDays(30)->toDateString();
                        }
                    } else {
                        $dueDate = Carbon::parse($invoiceDate)->addDays(30)->toDateString();
                    }

                    $isPaid = (int) ($legacy->lunas ?? 0) === 1;
                    $status = $isPaid ? 'Lunas' : 'Belum Lunas';

                    $paidAt = null;
                    if ($isPaid && ! empty($legacy->tanggal_lunas) && $legacy->tanggal_lunas !== '0000-00-00') {
                        try {
                            $paidAt = Carbon::parse($legacy->tanggal_lunas)->toDateString();
                        } catch (\Throwable) {
                            $paidAt = $invoiceDate;
                        }
                    } elseif ($isPaid) {
                        $paidAt = $invoiceDate;
                    }

                    // Find supplier
                    $supplier = $suppliersMap->get($legacy->id_supplier) ?? $defaultSupplier;
                    $supplierId = $supplier ? $supplier->id : null;
                    $supplierName = $supplier ? $supplier->name : 'Supplier ID '.$legacy->id_supplier;
                    $supplierCity = $supplier ? $supplier->address : null;
                    $supplierNpwp = $supplier ? $supplier->phone : null;

                    $subtotal = (float) ($legacy->jumlah ?? 0);
                    $discount = (float) ($legacy->diskon ?? 0);
                    $tax = (float) ($legacy->ppn ?? 0);
                    $total = (float) ($legacy->total ?? 0);
                    if ($total <= 0) {
                        $total = max(0, $subtotal - $discount + $tax);
                    }
                    $paidAmount = $isPaid ? ($legacy->jumlah_bayar ? (float) $legacy->jumlah_bayar : $total) : 0;

                    if ($isDryRun) {
                        $inserted++;

                        continue;
                    }

                    $invoice = Invoice::updateOrCreate(
                        ['legacy_id' => $legacy->id],
                        [
                            'invoice_number' => $invoiceNumber,
                            'reference_number' => 'PO-'.$legacy->id,
                            'invoice_date' => $invoiceDate,
                            'due_date' => $dueDate,
                            'supplier_id' => $supplierId,
                            'supplier_name' => $supplierName,
                            'supplier_city' => $supplierCity,
                            'supplier_npwp' => $supplierNpwp,
                            'status' => $status,
                            'notes' => $legacy->keterangan ?: null,
                            'paid_at' => $paidAt,
                            'subtotal_amount' => $subtotal,
                            'discount_amount' => $discount,
                            'tax_amount' => $tax,
                            'total_amount' => $total,
                            'paid_amount' => $paidAmount,
                        ]
                    );

                    // Insert or replace items
                    $invoiceDetails = $details->get($legacy->id) ?? collect();
                    $invoice->items()->delete();

                    if ($invoiceDetails->isEmpty()) {
                        // Create a fallback item if no detail existed
                        $invoice->items()->create([
                            'medicine_id' => $defaultMedicine?->id,
                            'medicine_name' => $defaultMedicine ? $defaultMedicine->name : 'Item Pembelian Farmasi',
                            'quantity' => 1,
                            'unit' => 'Box',
                            'price' => $total,
                            'discount' => 0,
                            'subtotal' => $total,
                        ]);
                    } else {
                        foreach ($invoiceDetails as $det) {
                            $med = $medicinesMap->get($det->id_obat) ?? $defaultMedicine;
                            $medId = $med ? $med->id : null;
                            $medName = $med ? $med->name : 'Obat ID '.$det->id_obat;
                            $unit = $med && $med->unit ? $med->unit->name : 'Box';

                            $qty = (float) ($det->qty ?? 1);
                            $price = (float) ($det->harga_beli ?? 0);
                            $itemDisc = (float) ($det->diskon ?? 0);
                            $itemSubtotal = round($qty * $price * max(0, 1 - $itemDisc / 100));

                            $expDate = null;
                            if (! empty($det->ed) && $det->ed !== '0000-00-00') {
                                try {
                                    $expDate = Carbon::parse($det->ed)->toDateString();
                                } catch (\Throwable) {
                                }
                            }

                            $invoice->items()->create([
                                'legacy_id' => $det->id,
                                'medicine_id' => $medId,
                                'medicine_name' => $medName,
                                'quantity' => $qty,
                                'unit' => $unit,
                                'price' => $price,
                                'discount' => $itemDisc,
                                'subtotal' => $itemSubtotal,
                                'batch_number' => $det->batch ?: null,
                                'expired_date' => $expDate,
                            ]);
                        }
                    }

                    $inserted++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->warn("Failed importing invoice ID {$legacy->id}: {$e->getMessage()}");
                }
            }
        });

        $this->info("Import completed! Inserted/Updated: {$inserted}, Failed: {$failed}");

        return 0;
    }
}
