<?php

namespace Database\Factories\Logistics;

use App\Models\Logistics\StockOpname;
use App\Models\Logistics\StockOpnameItem;
use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOpnameItem>
 */
class StockOpnameItemFactory extends Factory
{
    protected $model = StockOpnameItem::class;

    public function definition(): array
    {
        return [
            'stock_opname_id' => StockOpname::factory(),
            'warehouse_stock_id' => null,
            'medicine_id' => Medicine::factory(),
            'medicine_name' => 'Paracetamol 500 mg',
            'medicine_code' => 'OBT-'.$this->faker->unique()->numberBetween(100, 999),
            'unit' => 'Tablet',
            'batch_number' => 'BATCH-'.$this->faker->numberBetween(10, 99),
            'expired_date' => now()->addYear()->format('Y-m-d'),
            'system_stock' => 100,
            'physical_stock' => null,
            'difference' => null,
            'unit_price' => 1000,
            'difference_value' => null,
            'notes' => null,
        ];
    }
}
