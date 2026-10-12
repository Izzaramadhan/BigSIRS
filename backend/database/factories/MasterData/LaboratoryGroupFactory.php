<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\LaboratoryCategory;
use App\Models\MasterData\LaboratoryGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaboratoryGroupFactory extends Factory
{
    protected $model = LaboratoryGroup::class;

    public function definition(): array
    {
        return [
            'legacy_id' => $this->faker->unique()->randomNumber(5),
            'laboratory_category_id' => LaboratoryCategory::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(10000, 500000),
            'is_active' => true,
        ];
    }
}
