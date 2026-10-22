<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bed>
 */
class BedFactory extends Factory
{
    protected $model = Bed::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'name' => 'Bed ' . fake()->unique()->numerify('###'),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
