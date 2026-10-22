<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        return [
            'name' => 'Gedung '.fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
