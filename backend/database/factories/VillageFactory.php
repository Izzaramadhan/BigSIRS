<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Village>
 */
class VillageFactory extends Factory
{
    protected $model = Village::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'code' => $this->faker->unique()->numerify('110101####'),
            'name' => strtoupper($this->faker->citySuffix.' '.$this->faker->streetName),
            'is_active' => true,
        ];
    }
}
