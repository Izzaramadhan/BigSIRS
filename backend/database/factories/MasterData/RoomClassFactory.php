<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\RoomClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomClass>
 */
class RoomClassFactory extends Factory
{
    protected $model = RoomClass::class;

    public function definition(): array
    {
        return [
            'legacy_id' => null,
            'code' => 'KLS-' . fake()->unique()->numerify('##'),
            'name' => 'Kelas ' . fake()->unique()->word(),
            'price' => fake()->numberBetween(50000, 1500000),
            'description' => fake()->optional()->sentence(),
            'aplicare_code' => fake()->optional()->numerify('KL#'),
            'sirs_code' => fake()->optional()->numerify('##'),
        ];
    }
}
