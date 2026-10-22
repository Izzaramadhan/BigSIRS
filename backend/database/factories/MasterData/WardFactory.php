<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Floor;
use App\Models\MasterData\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ward>
 */
class WardFactory extends Factory
{
    protected $model = Ward::class;

    public function definition(): array
    {
        return [
            'floor_id' => Floor::factory(),
            'name' => 'Bangsal ' . fake()->unique()->word() . ' ' . fake()->randomLetter(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
