<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Room;
use App\Models\MasterData\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'ward_id' => Ward::factory(),
            'class_id' => null,
            'code' => 'KMR-' . fake()->unique()->numerify('####'),
            'name' => 'Kamar ' . fake()->unique()->numerify('###'),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
