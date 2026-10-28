<?php

namespace Database\Seeders;

use App\Models\Logistics\Invoice;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Try importing from legacy database if invoices table is empty
        if (Invoice::count() === 0) {
            try {
                Artisan::call('import:legacy-invoices', ['--limit' => 150]);
            } catch (\Throwable) {
                // Ignore if legacy connection is not available
            }
        }

        // 2. Also ensure current 2026 invoices exist with real active suppliers and medicines
        $suppliers = Supplier::where('is_active', true)->limit(10)->get();
        $medicines = Medicine::where('is_active', true)->with('unit')->limit(20)->get();

        if ($suppliers->isEmpty() || $medicines->isEmpty()) {
            return;
        }

        $sampleTemplates = [
            [
                'invoice_number' => 'FKT-KF-202609-0892',
                'reference_number' => 'PO-2026-0814',
                'invoice_date' => Carbon::now()->subDays(10)->toDateString(),
                'due_date' => Carbon::now()->addDays(20)->toDateString(),
                'status' => 'Belum Lunas',
                'notes' => 'Pengiriman batch 1 obat rawat inap',
                'items_count' => 4,
            ],
            [
                'invoice_number' => 'FKT-KLB-202609-0415',
                'reference_number' => 'PO-2026-0810',
                'invoice_date' => Carbon::now()->subDays(14)->toDateString(),
                'due_date' => Carbon::now()->addDays(16)->toDateString(),
                'status' => 'Lunas',
                'notes' => 'Pelunasan termin 1 via transfer bank',
                'items_count' => 3,
            ],
            [
                'invoice_number' => 'FKT-ENS-202609-0122',
                'reference_number' => 'PO-2026-0795',
                'invoice_date' => Carbon::now()->subDays(25)->toDateString(),
                'due_date' => Carbon::now()->subDays(5)->toDateString(),
                'status' => 'Belum Lunas',
                'notes' => 'Menunggu verifikasi faktur pajak',
                'items_count' => 3,
            ],
            [
                'invoice_number' => 'FKT-MBS-202609-0731',
                'reference_number' => 'PO-2026-0820',
                'invoice_date' => Carbon::now()->subDays(8)->toDateString(),
                'due_date' => Carbon::now()->addDays(22)->toDateString(),
                'status' => 'Lunas',
                'notes' => 'Pengiriman cairan infus & injeksi',
                'items_count' => 3,
            ],
            [
                'invoice_number' => 'FKT-AAM-202609-0309',
                'reference_number' => 'PO-2026-0805',
                'invoice_date' => Carbon::now()->subDays(18)->toDateString(),
                'due_date' => Carbon::now()->addDays(12)->toDateString(),
                'status' => 'Belum Lunas',
                'notes' => 'Faktur pengadaan obat kronis BPJS',
                'items_count' => 3,
            ],
            [
                'invoice_number' => 'FKT-TSP-202609-0551',
                'reference_number' => 'PO-2026-0822',
                'invoice_date' => Carbon::now()->subDays(5)->toDateString(),
                'due_date' => Carbon::now()->addDays(25)->toDateString(),
                'status' => 'Belum Lunas',
                'notes' => 'Pengadaan multivitamin dan analgetik sirup',
                'items_count' => 3,
            ],
            [
                'invoice_number' => 'FKT-DXA-202609-0210',
                'reference_number' => 'PO-2026-0780',
                'invoice_date' => Carbon::now()->subDays(40)->toDateString(),
                'due_date' => Carbon::now()->subDays(10)->toDateString(),
                'status' => 'Belum Lunas',
                'notes' => 'Pengadaan antibiotik spektrum luas',
                'items_count' => 2,
            ],
            [
                'invoice_number' => 'FKT-SNB-202609-0944',
                'reference_number' => 'PO-2026-0818',
                'invoice_date' => Carbon::now()->subDays(7)->toDateString(),
                'due_date' => Carbon::now()->addDays(23)->toDateString(),
                'status' => 'Lunas',
                'notes' => 'Pembelian produk salep dan anestesi lokal',
                'items_count' => 3,
            ],
            [
                'invoice_number' => 'FKT-KF-202609-0901',
                'reference_number' => 'PO-2026-0830',
                'invoice_date' => Carbon::now()->subDays(2)->toDateString(),
                'due_date' => Carbon::now()->addDays(28)->toDateString(),
                'status' => 'Belum Lunas',
                'notes' => 'Emergency order tablet antihipertensi',
                'items_count' => 2,
            ],
            [
                'invoice_number' => 'FKT-KLB-202609-0480',
                'reference_number' => 'PO-2026-0828',
                'invoice_date' => Carbon::now()->subDays(3)->toDateString(),
                'due_date' => Carbon::now()->addDays(27)->toDateString(),
                'status' => 'Belum Lunas',
                'notes' => 'Faktur alkes habis pakai dan antibiotik',
                'items_count' => 3,
            ],
        ];

        foreach ($sampleTemplates as $idx => $tmpl) {
            if (Invoice::where('invoice_number', $tmpl['invoice_number'])->exists()) {
                continue;
            }

            $supplier = $suppliers[$idx % $suppliers->count()];
            $isPaid = $tmpl['status'] === 'Lunas';
            $paidAt = $isPaid ? Carbon::parse($tmpl['invoice_date'])->addDays(1)->toDateString() : null;

            $invoice = Invoice::create([
                'invoice_number' => $tmpl['invoice_number'],
                'reference_number' => $tmpl['reference_number'],
                'invoice_date' => $tmpl['invoice_date'],
                'due_date' => $tmpl['due_date'],
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'supplier_city' => $supplier->address ?: 'Jakarta',
                'supplier_npwp' => $supplier->phone ?: '01.234.567.8-001.000',
                'status' => $tmpl['status'],
                'notes' => $tmpl['notes'],
                'paid_at' => $paidAt,
                'subtotal_amount' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
            ]);

            $total = 0;
            for ($i = 0; $i < $tmpl['items_count']; $i++) {
                $med = $medicines[($idx * 2 + $i) % $medicines->count()];
                $qty = rand(10, 100);
                $price = $med->selling_price_per_unit > 0 ? (float) $med->selling_price_per_unit : (rand(20, 150) * 1000);
                $disc = rand(0, 1) === 1 ? rand(2, 5) : 0;
                $subtotal = round($qty * $price * (1 - $disc / 100));
                $total += $subtotal;

                $invoice->items()->create([
                    'medicine_id' => $med->id,
                    'medicine_name' => $med->name,
                    'quantity' => $qty,
                    'unit' => $med->unit ? $med->unit->name : 'Box',
                    'price' => $price,
                    'discount' => $disc,
                    'subtotal' => $subtotal,
                    'batch_number' => 'BATCH-'.rand(1000, 9999),
                    'expired_date' => Carbon::now()->addYears(2)->toDateString(),
                ]);
            }

            $invoice->update([
                'subtotal_amount' => $total,
                'total_amount' => $total,
                'paid_amount' => $isPaid ? $total : 0,
            ]);
        }
    }
}
