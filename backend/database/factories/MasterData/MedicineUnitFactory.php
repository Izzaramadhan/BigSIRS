<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\MedicineUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicineUnit>
 */
class MedicineUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->words(2, true)),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
