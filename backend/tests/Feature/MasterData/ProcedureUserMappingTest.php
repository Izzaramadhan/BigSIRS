<?php

namespace Tests\Feature\MasterData;

use App\Models\Employee;
use App\Models\MasterData\MedicalProcedure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureUserMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Since sqlite in memory doesn't have foreign keys enforcement by default in older versions, it's fine.
    }

    public function test_api_returns_separated_name_and_profession()
    {
        $employee = Employee::create([
            'legacy_id' => 1694,
            'code' => '123',
            'name' => 'Person 1694',
            'profession' => 'Dokter',
            'is_active' => true,
        ]);

        $procedure = MedicalProcedure::create([
            'code' => 'KGA031',
            'name' => 'Konsultasi /Pemeriksaan /Medikasi',
            'is_active' => true,
        ]);

        $procedure->employees()->attach($employee->id);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/master-data/procedure-user-mappings');

        $response->assertStatus(200);

        $data = $response->json('data.0');

        $this->assertEquals('KGA031', $data['procedure']['code']);
        $this->assertCount(1, $data['employees']);

        $empData = $data['employees'][0];
        $this->assertEquals('Person 1694', $empData['name']);
        $this->assertEquals('Dokter', $empData['profession']);
        // Verify name is not fallback to profession
        $this->assertNotEquals($empData['name'], $empData['profession']);
    }

    public function test_delete_mapping_does_not_delete_employee()
    {
        $employee = Employee::create([
            'legacy_id' => 1694,
            'code' => '123',
            'name' => 'Person 1694',
            'profession' => 'Dokter',
            'is_active' => true,
        ]);

        $procedure = MedicalProcedure::create([
            'code' => 'KGA031',
            'name' => 'Konsultasi /Pemeriksaan /Medikasi',
            'is_active' => true,
        ]);

        $procedure->employees()->attach($employee->id);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/api/v1/master-data/procedure-user-mappings/{$procedure->id}");
        $response->assertStatus(200);

        // Mapping is deleted
        $this->assertDatabaseMissing('procedure_employee', [
            'procedure_id' => $procedure->id,
            'employee_id' => $employee->id,
        ]);

        // Employee still exists
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Person 1694',
        ]);

        // Procedure still exists
        $this->assertDatabaseHas('medical_procedures', [
            'id' => $procedure->id,
        ]);
    }
}
