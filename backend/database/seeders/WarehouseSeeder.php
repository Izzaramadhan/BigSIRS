<?php

namespace Database\Seeders;

use App\Models\MasterData\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Warehouse::factory()->count(10)->create();
    }
}
