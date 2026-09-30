<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('EMP-#####'),
            'name' => $this->faker->name(),
            'profession' => 'Dokter',
            'is_active' => true,
        ];
    }
}
