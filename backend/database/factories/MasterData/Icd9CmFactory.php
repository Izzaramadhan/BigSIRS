<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Icd9Cm;
use Illuminate\Database\Eloquent\Factories\Factory;

class Icd9CmFactory extends Factory
{
    protected $model = Icd9Cm::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('##.##'),
            'name' => $this->faker->sentence(3),
            'english_name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'is_active' => true,
            'needs_review' => false,
            'inacbg_code' => $this->faker->bothify('?##.##'),
            'inacbg_name' => $this->faker->sentence(4),
        ];
    }
}
