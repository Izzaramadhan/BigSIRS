<?php

namespace Database\Factories\MasterData;

use App\Models\MasterData\Doctor;
use App\Models\Employee;
use App\Models\MasterData\Specialization;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'specialization_id' => Specialization::factory(),
            'str_number' => $this->faker->numerify('##.##.##.##.##'),
            'sip_number' => $this->faker->numerify('SIP/###/####'),
            'sip_valid_until' => $this->faker->dateTimeBetween('now', '+5 years')->format('Y-m-d'),
            'bpjs_dpjp_code' => $this->faker->numerify('DPJP###'),
            'ihs_number' => $this->faker->uuid(),
            'is_active' => true,
        ];
    }
}
