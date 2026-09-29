<?php

namespace Tests\Feature\MasterData;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\MasterData\Icd10Code;

class Icd10CodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/v1/master-data/icd10');
        $response->assertStatus(401);
    }

    public function test_can_list_icd10_codes_with_pagination()
    {
        Icd10Code::factory()->count(15)->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/master-data/icd10');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'links'
        ]);
        
        $this->assertCount(10, $response->json('data')); // Default pagination
    }

    public function test_can_search_by_code()
    {
        Icd10Code::factory()->create(['code' => 'A00', 'name' => 'Cholera']);
        Icd10Code::factory()->create(['code' => 'B20', 'name' => 'HIV']);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/v1/master-data/icd10?search=A00');
        
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('A00', $response->json('data.0.code'));
    }

    public function test_can_create_icd10()
    {
        $user = User::factory()->create();
        $payload = [
            'code' => 'a01.0',
            'name' => 'Typhoid fever',
            'english_name' => 'Typhoid fever',
            'is_medical_history' => true,
            'is_active' => true,
            'class_1_tariff' => 100000,
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/master-data/icd10', $payload);
        $response->assertStatus(201);
        $this->assertEquals('A01.0', $response->json('data.code')); // Code should be uppercase
        $this->assertTrue($response->json('data.is_medical_history'));
        $this->assertEquals(100000, $response->json('data.class_1_tariff'));
        $this->assertEquals(0, $response->json('data.class_2_tariff')); // Default 0
    }

    public function test_duplicate_code_is_rejected()
    {
        Icd10Code::factory()->create(['code' => 'A00']);
        
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v1/master-data/icd10', [
            'code' => 'A00',
            'name' => 'Duplicate',
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_can_update_icd10()
    {
        $icd10 = Icd10Code::factory()->create(['code' => 'A00', 'name' => 'Old Name']);
        
        $user = User::factory()->create();
        $response = $this->actingAs($user)->putJson("/api/v1/master-data/icd10/{$icd10->id}", [
            'code' => 'a00',
            'name' => 'New Name',
        ]);
        
        $response->assertStatus(200);
        $this->assertEquals('New Name', $response->json('data.name'));
        $this->assertEquals('A00', $response->json('data.code'));
    }

    public function test_soft_delete_works()
    {
        $icd10 = Icd10Code::factory()->create(['code' => 'A00']);
        
        $user = User::factory()->create();
        $response = $this->actingAs($user)->deleteJson("/api/v1/master-data/icd10/{$icd10->id}");
        
        $response->assertStatus(200);
        $this->assertSoftDeleted('icd10_codes', ['id' => $icd10->id]);
    }
}
