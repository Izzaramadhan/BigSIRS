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

        $response = $this->actingAs($this->user)->putJson('/api/v1/master-data/icd9-cms/' . $icd9->id, $payload);

        $response->assertOk()
                 ->assertJsonPath('data.name', 'Updated Name')
                 ->assertJsonPath('data.is_active', false);
    }

    public function test_can_delete_icd9cm()
    {
        $icd9 = Icd9Cm::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson('/api/v1/master-data/icd9-cms/' . $icd9->id);

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
}
