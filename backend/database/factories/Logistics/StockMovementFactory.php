<?php

namespace Database\Factories\Logistics;

use App\Models\Logistics\StockMovement;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'medicine_id' => Medicine::factory(),
            'stock_mutation_id' => null,
            'stock_mutation_item_id' => null,
            'movement_type' => 'mutation',
            'direction' => 'OUT',
            'quantity' => 10,
            'balance_before' => 100,
            'balance_after' => 90,
            'batch_number' => null,
            'expired_date' => null,
            'occurred_at' => Carbon::now(),
            'created_by' => User::factory(),
        ];
    }
}

