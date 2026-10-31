<?php

namespace Database\Factories\Logistics;

use App\Models\Logistics\StockMutation;
use App\Models\Logistics\StockMutationItem;
use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMutationItem>
 */
class StockMutationItemFactory extends Factory
{
    protected $model = StockMutationItem::class;

    public function definition(): array
    {
        return [
            'stock_mutation_id' => StockMutation::factory(),
            'medicine_id' => Medicine::factory(),
            'medicine_name' => $this->faker->words(3, true),
            'quantity' => $this->faker->randomFloat(2, 1, 50),
            'unit' => 'Tablet',
            'batch_number' => $this->faker->optional()->bothify('BATCH-####'),
            'expired_date' => $this->faker->optional()->dateTimeBetween('+1 month', '+2 years')?->format('Y-m-d'),
        ];
    }
}

