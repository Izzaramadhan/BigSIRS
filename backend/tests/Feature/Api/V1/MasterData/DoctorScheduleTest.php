<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Doctor;
use App\Models\MasterData\DoctorSchedule;
use App\Models\Polyclinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/v1/master-data/doctor-schedules');
        $response->assertStatus(401);
    }

    public function test_can_list_schedules()
    {
        DoctorSchedule::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/doctor-schedules');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'doctor', 'polyclinic', 'day_of_week', 'start_time', 'end_time', 'is_holiday', 'online_quota',
                    ],
                ],
                'meta', 'links',
            ]);
    }

    public function test_can_create_schedule()
    {
        $doctor = Doctor::factory()->create();
        $polyclinic = Polyclinic::factory()->create();

        $data = [
            'doctor_id' => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'is_holiday' => false,
            'online_quota' => 20,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/doctor-schedules', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('doctor_schedules', [
            'doctor_id' => $doctor->id,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);
    }

    public function test_cannot_create_overlapping_schedule()
    {
        $doctor = Doctor::factory()->create();
        $polyclinic = Polyclinic::factory()->create();

        DoctorSchedule::factory()->create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $data = [
            'doctor_id' => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week' => 1,
            'start_time' => '11:00',
            'end_time' => '15:00',
            'is_holiday' => false,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/doctor-schedules', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time', 'end_time']);
    }

    public function test_can_create_consecutive_schedule()
    {
        $doctor = Doctor::factory()->create();
        $polyclinic = Polyclinic::factory()->create();

        DoctorSchedule::factory()->create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $data = [
            'doctor_id' => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week' => 1,
            'start_time' => '12:00',
            'end_time' => '15:00',
            'is_holiday' => false,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/doctor-schedules', $data);

        $response->assertStatus(201);
    }

    public function test_update_ignores_own_overlap()
    {
        $schedule = DoctorSchedule::factory()->create([
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $data = [
            'doctor_id' => $schedule->doctor_id,
            'polyclinic_id' => $schedule->polyclinic_id,
            'day_of_week' => 1,
            'start_time' => '07:00',
            'end_time' => '13:00',
            'is_holiday' => false,
        ];

        $response = $this->actingAs($this->user)->putJson('/api/v1/master-data/doctor-schedules/'.$schedule->id, $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('doctor_schedules', [
            'id' => $schedule->id,
            'start_time' => '07:00',
        ]);
    }

    public function test_can_delete_schedule()
    {
        $schedule = DoctorSchedule::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson('/api/v1/master-data/doctor-schedules/'.$schedule->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted('doctor_schedules', ['id' => $schedule->id]);
    }
}
