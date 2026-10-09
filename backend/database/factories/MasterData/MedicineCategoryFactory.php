<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\MedicineCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicineCategory>
 */
class MedicineCategoryFactory extends Factory
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
