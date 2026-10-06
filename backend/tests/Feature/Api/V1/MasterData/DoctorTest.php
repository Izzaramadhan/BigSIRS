<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Employee;
use App\Models\MasterData\Doctor;
use App\Models\MasterData\Specialization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DoctorTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_endpoints_require_authentication()
    {
        $this->getJson('/api/v1/master-data/doctors')->assertUnauthorized();
        $this->postJson('/api/v1/master-data/doctors', [])->assertUnauthorized();
    }

    public function test_can_list_doctors_with_pagination()
    {
        Doctor::factory()->count(15)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/doctors');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'specialization'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_can_search_by_name()
    {
        $employee = Employee::factory()->create(['name' => 'Dr. Budi Santoso']);
        $doctor = Doctor::factory()->create(['employee_id' => $employee->id]);
        Doctor::factory()->create(); // Another one

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/doctors?search=Budi');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Dr. Budi Santoso');
    }

    public function test_can_search_by_nik()
    {
        $employee = Employee::factory()->create(['national_id' => '330123456789']);
        $doctor = Doctor::factory()->create(['employee_id' => $employee->id]);
        Doctor::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/doctors?search=330123');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $doctor->id);
    }

    public function test_non_doctor_employee_does_not_appear_in_search()
    {
        $employee = Employee::factory()->create(['name' => 'Budi Bukan Dokter']);
        // Do NOT create a doctor profile for this employee

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/doctors?search=Budi');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_can_filter_by_specialization()
    {
        $spec1 = Specialization::factory()->create();
        $spec2 = Specialization::factory()->create();

        Doctor::factory()->create(['specialization_id' => $spec1->id]);
        Doctor::factory()->create(['specialization_id' => $spec2->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/doctors?specialization_id='.$spec1->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.specialization.id', $spec1->id);
    }

    public function test_can_filter_by_status()
    {
        Doctor::factory()->create(['is_active' => true]);
        Doctor::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/doctors?status=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_create_doctor_with_existing_employee()
    {
        Storage::fake('public');

        $employee = Employee::factory()->create();
        $spec = Specialization::factory()->create();

        $data = [
            'employee_id' => $employee->id,
            'person' => [
                'name' => $employee->name,
                'gender' => 'L',
                'national_id' => '1234567890123456',
            ],
            'professional' => [
                'specialization_id' => $spec->id,
                'str_number' => 'STR123',
                'sip_number' => 'SIP123',
                'sip_valid_until' => '2030-12-31',
                'bpjs_dpjp_code' => 'DPJP1',
                'is_active' => true,
            ],
            'signature' => UploadedFile::fake()->createWithContent('ttd.jpg', 'fake-image-content')->mimeType('image/jpeg'),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/doctors', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', $employee->name);

        $this->assertDatabaseHas('doctors', [
            'employee_id' => $employee->id,
            'str_number' => 'STR123',
        ]);
    }

    public function test_cannot_create_duplicate_doctor_for_same_employee()
    {
        $doctor = Doctor::factory()->create();

        $data = [
            'employee_id' => $doctor->employee_id,
            'person' => ['name' => 'test', 'gender' => 'L'],
            'professional' => ['specialization_id' => 1],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/doctors', $data);

        $response->assertJsonValidationErrors(['employee_id']);
    }

    public function test_can_show_doctor_detail()
    {
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/doctors/'.$doctor->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $doctor->id)
            ->assertJsonStructure([
                'data' => [
                    'str_number',
                    'sip_number',
                    'has_signature',
                    'employee' => [
                        'id',
                        'national_id',
                        'name',
                        'education_id',
                        'occupation_id',
                        'province_id',
                        'regency_id',
                        'district_id',
                        'village_id',
                    ],
                ],
            ]);
    }

    public function test_can_update_doctor()
    {
        $employee = Employee::factory()->create(['name' => 'Old Name']);
        $doctor = Doctor::factory()->create(['str_number' => 'OLD', 'employee_id' => $employee->id]);
        $spec = Specialization::factory()->create();

        $data = [
            'employee_id' => $doctor->employee_id,
            'person' => [
                'name' => 'New Name',
                'gender' => 'P',
            ],
            'professional' => [
                'specialization_id' => $spec->id,
                'str_number' => 'NEW_STR',
                'is_active' => true,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/master-data/doctors/'.$doctor->id, $data);

        $response->assertOk();
        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'str_number' => 'NEW_STR',
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'New Name',
        ]);
    }

    public function test_unique_validation_ignores_self_on_update()
    {
        $employee = Employee::factory()->create();
        $doctor = Doctor::factory()->create(['sip_number' => 'SIP1', 'employee_id' => $employee->id]);
        $spec = Specialization::factory()->create();

        $data = [
            'employee_id' => $doctor->employee_id,
            'person' => [
                'name' => $employee->name,
                'gender' => 'L',
            ],
            'professional' => [
                'specialization_id' => $spec->id,
                'sip_number' => 'SIP1',
                'is_active' => true,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/master-data/doctors/'.$doctor->id, $data);

        $response->assertOk(); // No validation error
    }

    public function test_create_validates_gender_is_required()
    {
        $employee = Employee::factory()->create();
        $spec = Specialization::factory()->create();

        $data = [
            'employee_id' => $employee->id,
            'person' => [
                'name' => $employee->name,
                // gender is missing
            ],
            'professional' => [
                'specialization_id' => $spec->id,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/doctors', $data);

        $response->assertJsonValidationErrors(['person.gender']);
    }

    public function test_create_validates_gender_is_valid_enum()
    {
        $employee = Employee::factory()->create();
        $spec = Specialization::factory()->create();

        $data = [
            'employee_id' => $employee->id,
            'person' => [
                'name' => $employee->name,
                'gender' => 'INVALID_GENDER',
            ],
            'professional' => [
                'specialization_id' => $spec->id,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/doctors', $data);

        $response->assertJsonValidationErrors(['person.gender']);
    }

    public function test_update_validates_gender_is_required()
    {
        $employee = Employee::factory()->create(['name' => 'Old Name', 'gender' => 'L']);
        $doctor = Doctor::factory()->create(['employee_id' => $employee->id]);
        $spec = Specialization::factory()->create();

        $data = [
            'employee_id' => $doctor->employee_id,
            'person' => [
                'name' => 'New Name',
                // gender is missing
            ],
            'professional' => [
                'specialization_id' => $spec->id,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/master-data/doctors/'.$doctor->id, $data);

        $response->assertJsonValidationErrors(['person.gender']);
    }

    public function test_can_update_status()
    {
        $doctor = Doctor::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/master-data/doctors/'.$doctor->id.'/status', [
                'is_active' => false,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'is_active' => false,
        ]);
    }

    public function test_archive_does_not_delete_employee()
    {
        $doctor = Doctor::factory()->create();
        $employeeId = $doctor->employee_id;

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/master-data/doctors/'.$doctor->id);

        $response->assertNoContent();

        $this->assertSoftDeleted('doctors', ['id' => $doctor->id]);
        $this->assertDatabaseHas('employees', ['id' => $employeeId, 'deleted_at' => null]);
    }

    public function test_lookup_only_returns_safe_fields()
    {
        Doctor::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/lookups/doctors');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'label', 'name', 'specialization_name', 'is_active'],
                ],
            ]);

        // Assert no sensitive fields are present
        $first = $response->json('data.0');
        $this->assertArrayNotHasKey('nik', $first);
        $this->assertArrayNotHasKey('sip_number', $first);
    }
}
