<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\RadiologyItemGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadiologyItemGroup>
 */
class RadiologyItemGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
