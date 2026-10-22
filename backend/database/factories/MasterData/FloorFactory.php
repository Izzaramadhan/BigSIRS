<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Building;
use App\Models\MasterData\Floor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Floor>
 */
class FloorFactory extends Factory
{
    protected $model = Floor::class;

    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'name' => 'Lantai ' . fake()->unique()->numberBetween(1, 100),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
