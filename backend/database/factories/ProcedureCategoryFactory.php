<?php

namespace Database\Factories;

use App\Models\ProcedureCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcedureCategory>
 */
class ProcedureCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legacy_id' => null,
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
