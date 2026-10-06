<?php

namespace Database\Factories;

use App\Models\Regency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\District>
 */
class DistrictFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legacy_id' => $this->faker->unique()->numerify('####'),
            'code' => $this->faker->unique()->numerify('#######'),
            'name' => 'KECAMATAN ' . strtoupper($this->faker->city()),
            'regency_id' => Regency::factory(),
            'is_active' => true,
        ];
    }
}
