<?php

namespace Database\Factories;

use App\Models\Guarantor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guarantor>
 */
class GuarantorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('ASR-####')),
            'name' => $this->faker->company(),
            'type' => $this->faker->randomElement(\App\Enums\GuarantorType::cases()),
            'is_active' => true,
        ];
    }
}
