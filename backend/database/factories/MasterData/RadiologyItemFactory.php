<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\RadiologyItem;
use App\Models\MasterData\RadiologyItemGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadiologyItem>
 */
class RadiologyItemFactory extends Factory
{
    protected $model = RadiologyItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->words(3, true)),
            'radiology_item_group_id' => RadiologyItemGroup::factory(),
            'is_active' => true,
        ];
    }
}
