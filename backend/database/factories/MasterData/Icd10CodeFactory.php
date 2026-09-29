<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Icd10Code;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Icd10Code>
 */
class Icd10CodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('?##.#')),
            'name' => $this->faker->words(3, true),
            'english_name' => $this->faker->words(3, true),
            'is_medical_history' => false,
            'is_active' => true,
        ];
    }
}
