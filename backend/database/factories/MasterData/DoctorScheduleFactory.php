<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Doctor;
use App\Models\MasterData\DoctorSchedule;
use App\Models\Polyclinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorSchedule>
 */
class DoctorScheduleFactory extends Factory
{
    protected $model = DoctorSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start_hour = $this->faker->numberBetween(7, 16);
        $end_hour = $start_hour + $this->faker->numberBetween(2, 6);

        return [
            'doctor_id' => Doctor::factory(),
            'polyclinic_id' => Polyclinic::factory(),
            'day_of_week' => $this->faker->numberBetween(1, 7),
            'start_time' => sprintf('%02d:00:00', $start_hour),
            'end_time' => sprintf('%02d:00:00', $end_hour),
            'is_holiday' => false,
            'online_quota' => $this->faker->numberBetween(10, 50),
        ];
    }
}
