<?php

namespace Database\Factories;

use App\Models\TariffType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TariffType>
 */
class TariffTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('TT###')),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
