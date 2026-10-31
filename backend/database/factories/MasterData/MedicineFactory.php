<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicine>
 */
class MedicineFactory extends Factory
{
    protected $model = Medicine::class;

    public function definition(): array
    {
        return [
            'legacy_id' => $this->faker->unique()->numberBetween(1, 999999),
            'code' => $this->faker->unique()->bothify('MED-#####'),
            'name' => $this->faker->words(3, true).' Tablet',
            'kfa_code' => $this->faker->optional()->numerify('##########'),
            'function' => $this->faker->optional()->word(),
            'medicine_unit_id' => MedicineUnit::factory(),
            'medicine_category_id' => null,
            'medicine_classification_id' => null,
            'medicine_route_id' => null,
            'generic_medicine_id' => null,
            'selling_price_per_unit' => $this->faker->numberBetween(1000, 50000),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}

