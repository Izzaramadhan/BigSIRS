<?php

namespace Database\Factories;

use App\Models\TariffComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TariffComponent>
 */
class TariffComponentFactory extends Factory
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
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
