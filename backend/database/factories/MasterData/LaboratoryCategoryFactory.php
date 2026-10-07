<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\LaboratoryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaboratoryCategoryFactory extends Factory
{
    protected $model = LaboratoryCategory::class;

    public function definition(): array
    {
        return [
            'legacy_id' => $this->faker->unique()->randomNumber(5),
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
