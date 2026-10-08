<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\RadiologyGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadiologyGroup>
 */
class RadiologyGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = \App\Models\MasterData\RadiologyGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'radiology_category_id' => \App\Models\MasterData\RadiologyCategory::factory(),
            'radiology_type_id' => \App\Models\MasterData\RadiologyType::factory(),
            'activity_type_id' => \App\Models\ActivityType::factory(),
            'price' => fake()->randomFloat(2, 10000, 100000),
            'interpretation_price' => fake()->randomFloat(2, 1000, 10000),
            'loinc_code' => fake()->optional()->word,
            'loinc_url' => fake()->optional()->url,
            'is_active' => true,
        ];
    }
}
