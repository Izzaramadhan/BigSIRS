<?php

namespace Database\Seeders;

use App\Models\Logistics\StockMutation;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use App\Services\Logistics\StockMutationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class StockMutationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Try importing from legacy database if stock mutations table is empty
        if (StockMutation::count() === 0) {
            try {
                Artisan::call('import:legacy-mutations');
            } catch (\Throwable) {
                // Ignore if legacy connection is not available
            }
        }
        $user = User::first() ?? User::factory()->create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@bigsirs.test',
        ]);

        $mainWarehouse = Warehouse::firstOrCreate(
            ['name' => 'Gudang Utama'],
            ['code' => 'GDF', 'description' => 'Gudang Farmasi Pusat']
        );

        $rajalWarehouse = Warehouse::firstOrCreate(
            ['name' => 'Apotek Rawat Jalan'],
            ['code' => 'AP-RJ', 'description' => 'Apotek Farmasi Rawat Jalan']
        );

        $igdWarehouse = Warehouse::firstOrCreate(
            ['name' => 'Apotek IGD'],
            ['code' => 'AP-IGD', 'description' => 'Depo Farmasi Emergency IGD']
        );

        $ranapWarehouse = Warehouse::firstOrCreate(
            ['name' => 'Apotek Rawat Inap'],
            ['code' => 'AP-RI', 'description' => 'Depo Farmasi Rawat Inap']
        );

        // Fetch medicines
        $medicines = Medicine::where('is_active', true)->with('unit')->limit(15)->get();

        if ($medicines->isEmpty()) {
            return;
        }

        // 1. Seed initial warehouse stocks (idempotent)
        foreach ($medicines as $idx => $med) {
            WarehouseStock::firstOrCreate(
                [
                    'warehouse_id' => $mainWarehouse->id,
                    'medicine_id' => $med->id,
                    'batch_number' => 'BATCH-'.(202600 + $idx),
                ],
                [
                    'quantity' => 500.00,
                    'expired_date' => Carbon::now()->addMonths(12 + $idx)->format('Y-m-d'),
                ]
            );

            // Also seed a non-batch stock for flexibility
            WarehouseStock::firstOrCreate(
                [
                    'warehouse_id' => $mainWarehouse->id,
                    'medicine_id' => $med->id,
                    'batch_number' => null,
                ],
                [
                    'quantity' => 200.00,
                    'expired_date' => null,
                ]
            );
        }

        // 2. Seed sample stock mutations if none exist
        if (StockMutation::count() === 0 && $medicines->count() >= 3) {
            $service = new StockMutationService;

            $samples = [
                [
                    'mutationDate' => Carbon::now()->subDays(2)->format('Y-m-d H:i:s'),
                    'sourceWarehouseId' => $mainWarehouse->id,
                    'targetWarehouseId' => $rajalWarehouse->id,
                    'notes' => 'Restock berkala poli rawat jalan',
                    'items' => [
                        ['medicineId' => $medicines[0]->id, 'quantity' => 50],
                        ['medicineId' => $medicines[1]->id, 'quantity' => 30],
                    ],
                ],
                [
                    'mutationDate' => Carbon::now()->subDays(1)->format('Y-m-d H:i:s'),
                    'sourceWarehouseId' => $mainWarehouse->id,
                    'targetWarehouseId' => $igdWarehouse->id,
                    'notes' => 'Buffer darurat kebutuhan IGD malam',
                    'items' => [
                        ['medicineId' => $medicines[2]->id, 'quantity' => 25],
                    ],
                ],
                [
                    'mutationDate' => Carbon::now()->format('Y-m-d H:i:s'),
                    'sourceWarehouseId' => $mainWarehouse->id,
                    'targetWarehouseId' => $ranapWarehouse->id,
                    'notes' => 'Distribusi rutin lantai 3',
                    'items' => [
                        ['medicineId' => $medicines[0]->id, 'quantity' => 20],
                    ],
                ],
            ];

            foreach ($samples as $sample) {
                try {
                    $service->createMutation($sample, $user->id);
                } catch (\Throwable) {
                    // Ignore seeding collision
                }
            }
        }
    }
}
