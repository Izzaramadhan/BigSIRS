<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\RadiologyCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class RadiologyCategoryFactory extends Factory
{
    protected $model = RadiologyCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word() . ' Category',
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
