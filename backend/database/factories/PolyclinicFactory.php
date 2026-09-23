<?php

namespace Database\Factories;

use App\Enums\ServiceType;
use App\Models\Polyclinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Polyclinic>
 */
class PolyclinicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code'              => strtoupper($this->faker->unique()->bothify('POLI-####')),
            'name'              => $this->faker->words(3, true),
            'service_type'      => $this->faker->randomElement(ServiceType::cases())->value,
            'description'       => $this->faker->optional()->sentence(),
            'is_visible'        => true,
            'is_online_visible' => false,
            'quota'             => $this->faker->numberBetween(0, 50),
            'jkn_quota'         => $this->faker->numberBetween(0, 30),
            'bpjs_code'         => $this->faker->optional()->numerify('BPJS-####'),
            'satusehat_code'    => $this->faker->optional()->uuid(),
            'is_active'         => true,
        ];
    }
}
