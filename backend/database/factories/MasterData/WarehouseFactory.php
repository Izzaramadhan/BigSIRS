<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legacy_id' => $this->faker->unique()->numberBetween(1, 10000),
            'code' => $this->faker->unique()->lexify('???'),
            'name' => $this->faker->company().' Warehouse',
            'description' => $this->faker->sentence(),
        ];
    }
}
