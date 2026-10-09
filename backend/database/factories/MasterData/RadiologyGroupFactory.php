<?php

namespace Database\Factories\MasterData;

use App\Models\ActivityType;
use App\Models\MasterData\RadiologyCategory;
use App\Models\MasterData\RadiologyGroup;
use App\Models\MasterData\RadiologyType;
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
    protected $model = RadiologyGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'radiology_category_id' => RadiologyCategory::factory(),
            'radiology_type_id' => RadiologyType::factory(),
            'activity_type_id' => ActivityType::factory(),
            'price' => fake()->randomFloat(2, 10000, 100000),
            'interpretation_price' => fake()->randomFloat(2, 1000, 10000),
            'loinc_code' => fake()->optional()->word,
            'loinc_url' => fake()->optional()->url,
            'is_active' => true,
        ];
    }
}
