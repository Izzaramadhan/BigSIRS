<?php

namespace Database\Seeders;

use App\Models\Logistics\StockOpname;
use App\Models\Logistics\StockOpnameItem;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class StockOpnameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Try importing from legacy database if available
        try {
            Artisan::call('legacy:import-stock-opnames');
        } catch (\Throwable) {
            // Ignore if legacy connection is not available
        }

        if (StockOpname::count() >= 10) {
            return;
        }

        $user = User::first() ?? User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
        ]);

        $warehouse = Warehouse::where('code', 'GDF')->first() ?? Warehouse::first();
        if (! $warehouse) {
            $warehouse = Warehouse::create([
                'name' => 'Gudang Utama',
                'code' => 'GDF',
                'description' => 'Gudang Farmasi Pusat',
            ]);
        }

        // Fetch available warehouse stocks with medicine
        $stocks = WarehouseStock::where('warehouse_id', $warehouse->id)
            ->with(['medicine.unit'])
            ->orderBy('id')
            ->limit(10)
            ->get();

        $now = Carbon::now();

        // 1. Sesi DRAFT: Belum ada snapshot sediaan
        StockOpname::updateOrCreate(
            ['opname_number' => 'SO-'.$now->format('Ym').'-0001'],
            [
                'warehouse_id' => $warehouse->id,
                'title' => 'Draft Audit Persediaan Awal Periode',
                'type' => 'Audit Rutin',
                'status' => StockOpname::STATUS_DRAFT,
                'start_date' => $now->copy()->subHours(1),
                'location_detail' => 'Rak A1 - Rak B2',
                'officer_name' => 'Apt. Siti Rahma, S.Farm',
                'officer_role' => 'Koordinator Farmasi',
                'notes' => 'Persiapan audit persediaan obat oral dan sirup awal bulan.',
                'total_items' => 0,
                'counted_items' => 0,
                'difference_count' => 0,
                'total_difference_value' => 0,
                'created_by' => $user->id,
            ]
        );

        // 2. Sesi COUNTING: Sedang dihitung fisik (sebagian terhitung, sebagian belum)
        $countingOpname = StockOpname::updateOrCreate(
            ['opname_number' => 'SO-'.$now->format('Ym').'-0002'],
            [
                'warehouse_id' => $warehouse->id,
                'title' => 'Audit Fisik Depo Gudang Farmasi',
                'type' => 'Audit Parsial',
                'status' => StockOpname::STATUS_COUNTING,
                'start_date' => $now->copy()->subHours(3),
                'location_detail' => 'Lantai 1 - Rak Injeksi',
                'officer_name' => 'Budi Santoso, A.Md.Farm',
                'officer_role' => 'Staf Logistik',
                'notes' => 'Perhitungan fisik sediaan injeksi dan antibiotik.',
                'created_by' => $user->id,
            ]
        );

        // Populate items snapshot for counting
        if ($stocks->isNotEmpty()) {
            $countingOpname->items()->delete();
            $countedCount = 0;
            $diffCount = 0;
            $totalDiffVal = 0.0;

            foreach ($stocks->take(4) as $idx => $stock) {
                $med = $stock->medicine;
                if (! $med) {
                    continue;
                }

                $sysStock = (float) $stock->quantity;
                $unitPrice = (float) ($med->selling_price_per_unit ?? 1000);

                // Count first 2 items, leave others uncounted
                $isCounted = $idx < 2;
                $physStock = $isCounted ? ($idx === 0 ? $sysStock : $sysStock - 2) : null;
                $diff = $physStock !== null ? $physStock - $sysStock : null;
                $diffVal = $diff !== null ? $diff * $unitPrice : null;

                if ($isCounted) {
                    $countedCount++;
                    if ($diff != 0) {
                        $diffCount++;
                        $totalDiffVal += $diffVal;
                    }
                }

                StockOpnameItem::create([
                    'stock_opname_id' => $countingOpname->id,
                    'warehouse_stock_id' => $stock->id,
                    'medicine_id' => $med->id,
                    'medicine_name' => $med->name,
                    'medicine_code' => $med->code,
                    'unit' => $med->unit?->name ?? 'Unit',
                    'batch_number' => $stock->batch_number,
                    'expired_date' => $stock->expired_date,
                    'system_stock' => $sysStock,
                    'physical_stock' => $physStock,
                    'difference' => $diff,
                    'unit_price' => $unitPrice,
                    'difference_value' => $diffVal,
                    'counted_by' => $isCounted ? $user->id : null,
                    'counted_at' => $isCounted ? $now->copy()->subMinutes(30) : null,
                ]);
            }

            $countingOpname->update([
                'total_items' => min(4, $stocks->count()),
                'counted_items' => $countedCount,
                'difference_count' => $diffCount,
                'total_difference_value' => $totalDiffVal,
            ]);
        }

        // 3. Sesi REVIEW: Seluruh item selesai dihitung, menunggu peninjauan apoteker penanggung jawab
        $reviewOpname = StockOpname::updateOrCreate(
            ['opname_number' => 'SO-'.$now->format('Ym').'-0003'],
            [
                'warehouse_id' => $warehouse->id,
                'title' => 'Audit Stok Rutin Bulanan Gudang Utama',
                'type' => 'Audit Bulanan',
                'status' => StockOpname::STATUS_REVIEW,
                'start_date' => $now->copy()->subHours(6),
                'location_detail' => 'Seluruh Zona Gudang',
                'officer_name' => 'Apt. Siti Rahma, S.Farm',
                'officer_role' => 'Koordinator Farmasi',
                'notes' => 'Menunggu verifikasi selisih fisik oleh kepala instalasi farmasi.',
                'created_by' => $user->id,
                'submitted_by' => $user->id,
                'submitted_at' => $now->copy()->subHours(1),
            ]
        );

        if ($stocks->isNotEmpty()) {
            $reviewOpname->items()->delete();
            $diffCount = 0;
            $totalDiffVal = 0.0;

            foreach ($stocks->take(3) as $idx => $stock) {
                $med = $stock->medicine;
                if (! $med) {
                    continue;
                }

                $sysStock = (float) $stock->quantity;
                $unitPrice = (float) ($med->selling_price_per_unit ?? 1500);

                // Introduce a discrepancy on 1 item
                $physStock = $idx === 1 ? max(0.0, $sysStock - 5.0) : $sysStock;
                $diff = $physStock - $sysStock;
                $diffVal = $diff * $unitPrice;

                if ($diff != 0) {
                    $diffCount++;
                    $totalDiffVal += $diffVal;
                }

                StockOpnameItem::create([
                    'stock_opname_id' => $reviewOpname->id,
                    'warehouse_stock_id' => $stock->id,
                    'medicine_id' => $med->id,
                    'medicine_name' => $med->name,
                    'medicine_code' => $med->code,
                    'unit' => $med->unit?->name ?? 'Unit',
                    'batch_number' => $stock->batch_number,
                    'expired_date' => $stock->expired_date,
                    'system_stock' => $sysStock,
                    'physical_stock' => $physStock,
                    'difference' => $diff,
                    'unit_price' => $unitPrice,
                    'difference_value' => $diffVal,
                    'notes' => $diff != 0 ? 'Selisih 5 unit karena kerusakan kemasan sekunder' : 'Sesuai fisik',
                    'counted_by' => $user->id,
                    'counted_at' => $now->copy()->subHours(2),
                ]);
            }

            $reviewOpname->update([
                'total_items' => min(3, $stocks->count()),
                'counted_items' => min(3, $stocks->count()),
                'difference_count' => $diffCount,
                'total_difference_value' => $totalDiffVal,
            ]);
        }

        // 4. Sesi COMPLETED: Selesai disetujui & penyesuaian stok diterapkan
        $completedOpname = StockOpname::updateOrCreate(
            ['opname_number' => 'SO-'.$now->format('Ym').'-0004'],
            [
                'warehouse_id' => $warehouse->id,
                'title' => 'Audit Triwulan Farmasi Gudang Utama',
                'type' => 'Audit Rutin',
                'status' => StockOpname::STATUS_COMPLETED,
                'start_date' => $now->copy()->subDays(2),
                'end_date' => $now->copy()->subDays(1),
                'location_detail' => 'Gudang Utama - Seluruh Rak',
                'officer_name' => 'Apt. Siti Rahma, S.Farm',
                'officer_role' => 'Koordinator Farmasi',
                'notes' => 'Audit persediaan triwulan telah disetujui dan saldo stok telah disesuaikan.',
                'created_by' => $user->id,
                'submitted_by' => $user->id,
                'submitted_at' => $now->copy()->subDays(1)->subHours(4),
                'approved_by' => $user->id,
                'approved_at' => $now->copy()->subDays(1),
            ]
        );

        if ($stocks->isNotEmpty()) {
            $completedOpname->items()->delete();
            $diffCount = 0;
            $totalDiffVal = 0.0;

            foreach ($stocks->take(3) as $idx => $stock) {
                $med = $stock->medicine;
                if (! $med) {
                    continue;
                }

                $sysStock = (float) $stock->quantity;
                $unitPrice = (float) ($med->selling_price_per_unit ?? 2000);
                $physStock = $idx === 2 ? $sysStock + 3.0 : $sysStock;
                $diff = $physStock - $sysStock;
                $diffVal = $diff * $unitPrice;

                if ($diff != 0) {
                    $diffCount++;
                    $totalDiffVal += $diffVal;
                }

                StockOpnameItem::create([
                    'stock_opname_id' => $completedOpname->id,
                    'warehouse_stock_id' => $stock->id,
                    'medicine_id' => $med->id,
                    'medicine_name' => $med->name,
                    'medicine_code' => $med->code,
                    'unit' => $med->unit?->name ?? 'Unit',
                    'batch_number' => $stock->batch_number,
                    'expired_date' => $stock->expired_date,
                    'system_stock' => $sysStock,
                    'physical_stock' => $physStock,
                    'difference' => $diff,
                    'unit_price' => $unitPrice,
                    'difference_value' => $diffVal,
                    'notes' => $diff != 0 ? 'Surplus 3 unit dari retur depo rawat inap' : 'Cocok',
                    'counted_by' => $user->id,
                    'counted_at' => $now->copy()->subDays(1)->subHours(5),
                ]);
            }

            $completedOpname->update([
                'total_items' => min(3, $stocks->count()),
                'counted_items' => min(3, $stocks->count()),
                'difference_count' => $diffCount,
                'total_difference_value' => $totalDiffVal,
            ]);
        }

        // 5. Sesi CANCELLED: Dibatalkan
        StockOpname::updateOrCreate(
            ['opname_number' => 'SO-'.$now->format('Ym').'-0005'],
            [
                'warehouse_id' => $warehouse->id,
                'title' => 'Audit Khusus Sirup Anak (Dibatalkan)',
                'type' => 'Audit Khusus',
                'status' => StockOpname::STATUS_CANCELLED,
                'start_date' => $now->copy()->subDays(5),
                'end_date' => $now->copy()->subDays(5)->addHours(2),
                'location_detail' => 'Rak Sirup & Drop C1',
                'officer_name' => 'Budi Santoso, A.Md.Farm',
                'officer_role' => 'Staf Logistik',
                'notes' => 'Dibatalkan karena jadwal berbenturan dengan inspeksi BPOM.',
                'cancel_reason' => 'Jadwal berbenturan dengan agenda inspeksi sarana kefarmasian oleh dinas/BPOM.',
                'total_items' => 0,
                'counted_items' => 0,
                'difference_count' => 0,
                'total_difference_value' => 0,
                'created_by' => $user->id,
                'cancelled_by' => $user->id,
                'cancelled_at' => $now->copy()->subDays(5)->addHours(2),
            ]
        );
    }
}
