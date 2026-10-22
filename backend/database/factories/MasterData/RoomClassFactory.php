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
            'description' => fake()->optional()->sentence(),
        ];
    }
}
