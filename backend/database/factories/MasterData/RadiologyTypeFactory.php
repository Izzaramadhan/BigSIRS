<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\RadiologyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadiologyType>
 */
class RadiologyTypeFactory extends Factory
{
    protected $model = RadiologyType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->word()),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
