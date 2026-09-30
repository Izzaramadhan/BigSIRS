<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Specialization;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecializationFactory extends Factory
{
    protected $model = Specialization::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('SPEC-???'),
            'name' => $this->faker->words(3, true),
            'is_active' => true,
        ];
    }
}
