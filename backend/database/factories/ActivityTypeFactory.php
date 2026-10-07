<?php

namespace Database\Factories;

use App\Models\ActivityType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityType>
 */
class ActivityTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legacy_id' => $this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->unique()->words(3, true),
            'parent_id' => null,
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
