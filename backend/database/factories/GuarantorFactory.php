<?php

namespace Database\Factories;

use App\Enums\GuarantorType;
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
            'type' => $this->faker->randomElement(GuarantorType::cases()),
            'is_active' => true,
            'is_government' => $this->faker->boolean(),
            'inacbg_id' => $this->faker->optional()->numerify('##'),
        ];
    }
}
