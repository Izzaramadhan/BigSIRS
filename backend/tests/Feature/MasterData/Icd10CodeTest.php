<?php

namespace Tests\Feature\MasterData;

use App\Models\MasterData\Icd10Code;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
            'links',
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

    public function test_can_search_by_inacbg_code()
    {
        Icd10Code::factory()->create(['code' => 'A01', 'name' => 'Cholera', 'inacbg_code' => 'I-4-10-I']);
        Icd10Code::factory()->create(['code' => 'B01', 'name' => 'Varicella', 'inacbg_code' => 'U-1-01-I']);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/v1/master-data/icd10?search=I-4-10-I');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('A01', $response->json('data.0.code'));
    }

    public function test_can_sort_by_column()
    {
        Icd10Code::factory()->create(['code' => 'B01', 'name' => 'Zebra']);
        Icd10Code::factory()->create(['code' => 'A01', 'name' => 'Apple']);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/v1/master-data/icd10?sort=name&direction=asc');

        $response->assertStatus(200);
        $this->assertEquals('Apple', $response->json('data.0.name'));
    }

    public function test_can_create_and_update_with_all_10_fields()
    {
        $user = User::factory()->create();
        $payload = [
            'code' => 'z99.9',
            'name' => 'Dependence on unspecified enabling machine or device',
            'english_name' => 'Dependence on unspecified enabling machine',
            'description' => 'Pasien dengan alat bantu',
            'inacbg_code' => 'Z-3-11-I',
            'inacbg_name' => 'Prosedur Rawat Jalan Lanjutan',
            'class_1_tariff' => 1500000,
            'class_2_tariff' => 1200000,
            'class_3_tariff' => 900000,
            'is_medical_history' => true,
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/master-data/icd10', $payload);
        $response->assertStatus(201);
        $id = $response->json('data.id');
        $this->assertEquals('Z99.9', $response->json('data.code'));
        $this->assertEquals('Dependence on unspecified enabling machine or device', $response->json('data.name'));
        $this->assertEquals('Dependence on unspecified enabling machine', $response->json('data.english_name'));
        $this->assertEquals('Pasien dengan alat bantu', $response->json('data.description'));
        $this->assertEquals('Z-3-11-I', $response->json('data.inacbg_code'));
        $this->assertEquals('Prosedur Rawat Jalan Lanjutan', $response->json('data.inacbg_name'));
        $this->assertEquals(1500000, $response->json('data.class_1_tariff'));
        $this->assertEquals(1200000, $response->json('data.class_2_tariff'));
        $this->assertEquals(900000, $response->json('data.class_3_tariff'));
        $this->assertTrue($response->json('data.is_medical_history'));

        // Update
        $updatePayload = [
            'code' => 'z99.9',
            'name' => 'Updated Name',
            'english_name' => 'Updated EN',
            'description' => 'Updated desc',
            'inacbg_code' => 'Z-3-11-II',
            'inacbg_name' => 'Updated INACBG Name',
            'class_1_tariff' => 2000000,
            'class_2_tariff' => 1700000,
            'class_3_tariff' => 1100000,
            'is_medical_history' => false,
        ];
        $updateResponse = $this->actingAs($user)->putJson("/api/v1/master-data/icd10/{$id}", $updatePayload);
        $updateResponse->assertStatus(200);
        $this->assertEquals('Updated Name', $updateResponse->json('data.name'));
        $this->assertEquals(2000000, $updateResponse->json('data.class_1_tariff'));
        $this->assertFalse($updateResponse->json('data.is_medical_history'));
    }
}
