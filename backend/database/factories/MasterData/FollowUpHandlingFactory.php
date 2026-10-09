<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\FollowUpHandling;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MasterData\FollowUpHandling>
 */
class FollowUpHandlingFactory extends Factory
{
    protected $model = FollowUpHandling::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->optional()->lexify('???'),
            'is_active' => true,
        ];
    }
}
