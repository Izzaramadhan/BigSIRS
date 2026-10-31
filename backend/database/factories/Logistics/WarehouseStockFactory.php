<?php

namespace Database\Factories\Logistics;

use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseStock>
 */
class WarehouseStockFactory extends Factory
{
    protected $model = WarehouseStock::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'medicine_id' => Medicine::factory(),
            'quantity' => $this->faker->randomFloat(2, 10, 500),
            'batch_number' => $this->faker->optional()->bothify('BATCH-####'),
            'expired_date' => $this->faker->optional()->dateTimeBetween('+1 month', '+2 years')?->format('Y-m-d'),
        ];
    }
}

