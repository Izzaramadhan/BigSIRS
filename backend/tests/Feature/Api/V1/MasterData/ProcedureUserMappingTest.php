<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Employee;
use App\Models\MasterData\MedicalProcedure;
use App\Models\ProcedureCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureUserMappingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_endpoint_requires_auth()
    {
        $response = $this->getJson('/api/v1/master-data/procedure-user-mappings');
        $response->assertStatus(401);
    }

    public function test_can_list_mappings_with_pagination()
    {
        $cat = ProcedureCategory::factory()->create();
        $procedure = MedicalProcedure::create(['code' => 'P1', 'name' => 'Proc 1', 'procedure_category_id' => $cat->id]);
        $employee = Employee::create(['name' => 'Emp 1', 'code' => '123', 'is_active' => true]);
        $procedure->employees()->attach($employee->id);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/procedure-user-mappings?per_page=10');
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'procedure', 'employees']], 'meta' => ['total']]);

        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_lookup_employees_read_only()
    {
        Employee::create(['name' => 'Emp Active', 'code' => 'A', 'is_active' => true]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/lookups/employees?search=Active');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_create_mapping()
    {
        $cat = ProcedureCategory::factory()->create();
        $procedure = MedicalProcedure::create(['code' => 'P1', 'name' => 'Proc 1', 'procedure_category_id' => $cat->id]);
        $emp1 = Employee::create(['name' => 'Emp 1', 'is_active' => true]);
        $emp2 = Employee::create(['name' => 'Emp 2', 'is_active' => true]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/procedure-user-mappings', [
            'procedure_id' => $procedure->id,
            'employee_ids' => [$emp1->id, $emp2->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('procedure_employee', ['procedure_id' => $procedure->id, 'employee_id' => $emp1->id]);
        $this->assertDatabaseHas('procedure_employee', ['procedure_id' => $procedure->id, 'employee_id' => $emp2->id]);
    }

    public function test_reject_duplicate_mapping_for_same_procedure()
    {
        $cat = ProcedureCategory::factory()->create();
        $procedure = MedicalProcedure::create(['code' => 'P1', 'name' => 'Proc 1', 'procedure_category_id' => $cat->id]);
        $emp1 = Employee::create(['name' => 'Emp 1', 'is_active' => true]);

        $procedure->employees()->attach($emp1->id);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/procedure-user-mappings', [
            'procedure_id' => $procedure->id,
            'employee_ids' => [$emp1->id],
        ]);

        $response->assertStatus(422);
    }

    public function test_can_update_mapping()
    {
        $cat = ProcedureCategory::factory()->create();
        $procedure = MedicalProcedure::create(['code' => 'P1', 'name' => 'Proc 1', 'procedure_category_id' => $cat->id]);
        $emp1 = Employee::create(['name' => 'Emp 1', 'is_active' => true]);
        $emp2 = Employee::create(['name' => 'Emp 2', 'is_active' => true]);

        $procedure->employees()->attach($emp1->id);

        $response = $this->actingAs($this->user)->putJson('/api/v1/master-data/procedure-user-mappings/'.$procedure->id, [
            'procedure_id' => $procedure->id,
            'employee_ids' => [$emp2->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('procedure_employee', ['procedure_id' => $procedure->id, 'employee_id' => $emp1->id]);
        $this->assertDatabaseHas('procedure_employee', ['procedure_id' => $procedure->id, 'employee_id' => $emp2->id]);
    }

    public function test_delete_mapping_does_not_delete_procedure_or_employee()
    {
        $cat = ProcedureCategory::factory()->create();
        $procedure = MedicalProcedure::create(['code' => 'P1', 'name' => 'Proc 1', 'procedure_category_id' => $cat->id]);
        $emp1 = Employee::create(['name' => 'Emp 1', 'is_active' => true]);

        $procedure->employees()->attach($emp1->id);

        $response = $this->actingAs($this->user)->deleteJson('/api/v1/master-data/procedure-user-mappings/'.$procedure->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('procedure_employee', ['procedure_id' => $procedure->id, 'employee_id' => $emp1->id]);
        $this->assertDatabaseHas('medical_procedures', ['id' => $procedure->id]);
        $this->assertDatabaseHas('employees', ['id' => $emp1->id]);
    }
}
