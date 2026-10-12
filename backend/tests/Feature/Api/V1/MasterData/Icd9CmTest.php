<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Icd9Cm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Icd9CmTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_cannot_access_endpoints()
    {
        $this->getJson('/api/v1/master-data/icd9-cms')->assertUnauthorized();
        $this->getJson('/api/v1/lookups/icd9-cms')->assertUnauthorized();
    }

    public function test_can_list_icd9cm()
    {
        Icd9Cm::factory()->count(15)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/icd9-cms');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure(['data' => [['id', 'code', 'name']], 'meta', 'links']);
    }

    public function test_can_create_icd9cm()
    {
        $payload = [
            'code' => '99.99',
            'name' => 'Test Procedure',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/icd9-cms', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.code', '99.99');

        $this->assertDatabaseHas('icd9_cms', ['code' => '99.99']);
    }

    public function test_can_update_icd9cm()
    {
        $icd9 = Icd9Cm::factory()->create();

        $payload = [
            'code' => $icd9->code,
            'name' => 'Updated Name',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user)->putJson('/api/v1/master-data/icd9-cms/'.$icd9->id, $payload);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_can_delete_icd9cm()
    {
        $icd9 = Icd9Cm::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson('/api/v1/master-data/icd9-cms/'.$icd9->id);

        $response->assertNoContent();
        $this->assertSoftDeleted('icd9_cms', ['id' => $icd9->id]);
    }

    public function test_can_search_icd9cm()
    {
        Icd9Cm::factory()->create(['code' => '11.11', 'name' => 'First']);
        Icd9Cm::factory()->create(['code' => '22.22', 'name' => 'Second']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/icd9-cms?search=11.11');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', '11.11');
    }

    public function test_duplicate_code_is_rejected()
    {
        Icd9Cm::factory()->create(['code' => '11.11']);

        $payload = [
            'code' => '11.11',
            'name' => 'Test',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/icd9-cms', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_can_create_and_update_with_tariffs_and_inacbg()
    {
        $payload = [
            'code' => '87.44',
            'name' => 'Routine chest x-ray, so described',
            'inacbg_code' => 'U-1-01-I',
            'inacbg_name' => 'Prosedur Radiologi',
            'class_1_tariff' => 750000,
            'class_2_tariff' => 500000,
            'class_3_tariff' => 350000,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/icd9-cms', $payload);
        $response->assertCreated();
        $this->assertEquals('87.44', $response->json('data.code'));
        $this->assertEquals('Routine chest x-ray, so described', $response->json('data.name'));
        $this->assertEquals('U-1-01-I', $response->json('data.inacbg_code'));
        $this->assertEquals('Prosedur Radiologi', $response->json('data.inacbg_name'));
        $this->assertEquals(750000, $response->json('data.class_1_tariff'));
        $this->assertEquals(500000, $response->json('data.class_2_tariff'));
        $this->assertEquals(350000, $response->json('data.class_3_tariff'));

        $id = $response->json('data.id');

        // Update
        $updatePayload = [
            'code' => '87.44',
            'name' => 'Updated Chest X-Ray',
            'inacbg_code' => 'U-1-01-II',
            'inacbg_name' => 'Updated INACBG',
            'class_1_tariff' => 900000,
            'class_2_tariff' => 600000,
            'class_3_tariff' => 400000,
        ];

        $updateResponse = $this->actingAs($this->user)->putJson("/api/v1/master-data/icd9-cms/{$id}", $updatePayload);
        $updateResponse->assertOk();
        $this->assertEquals('Updated Chest X-Ray', $updateResponse->json('data.name'));
        $this->assertEquals('U-1-01-II', $updateResponse->json('data.inacbg_code'));
        $this->assertEquals(900000, $updateResponse->json('data.class_1_tariff'));
    }

    public function test_can_sort_by_column()
    {
        Icd9Cm::factory()->create(['code' => '88.01', 'name' => 'Zebra Scan']);
        Icd9Cm::factory()->create(['code' => '88.02', 'name' => 'Apple Scan']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/icd9-cms?sort=name&direction=asc');
        $response->assertOk();
        $this->assertEquals('Apple Scan', $response->json('data.0.name'));
    }

    public function test_can_search_by_inacbg_code()
    {
        Icd9Cm::factory()->create(['code' => '11.01', 'name' => 'Proc 1', 'inacbg_code' => 'INAC-123']);
        Icd9Cm::factory()->create(['code' => '11.02', 'name' => 'Proc 2', 'inacbg_code' => 'OTHER-456']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/icd9-cms?search=INAC-123');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('11.01', $response->json('data.0.code'));
    }
}
