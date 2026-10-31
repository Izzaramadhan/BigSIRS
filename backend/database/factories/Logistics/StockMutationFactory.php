<?php

namespace Database\Factories\Logistics;

use App\Models\Logistics\StockMutation;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMutation>
 */
class StockMutationFactory extends Factory
{
    protected $model = StockMutation::class;

    public function definition(): array
    {
        $date = Carbon::now();

        return [
            'legacy_id' => null,
            'mutation_number' => 'MUT-'.$date->format('Ym').'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'mutation_date' => $date,
            'source_warehouse_id' => Warehouse::factory(),
            'target_warehouse_id' => Warehouse::factory(),
            'notes' => $this->faker->sentence(),
            'status' => 'validated',
            'created_by' => User::factory(),
            'validated_by' => User::factory(),
            'validated_at' => $date,
        ];
    }
}

