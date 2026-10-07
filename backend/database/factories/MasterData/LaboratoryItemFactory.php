<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\LaboratoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaboratoryItem>
 */
class LaboratoryItemFactory extends Factory
{
    protected $model = LaboratoryItem::class;

    public function definition(): array
    {
        return [
            'legacy_id' => null,
            'name' => fake()->unique()->words(2, true),
            'reference_value' => fake()->randomElement(['Negatif', '30-70', '<200', null]),
            'unit' => fake()->randomElement(['mg/dL', 'g/dL', null]),
            'is_active' => true,
        ];
    }
}
