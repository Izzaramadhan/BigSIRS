<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
