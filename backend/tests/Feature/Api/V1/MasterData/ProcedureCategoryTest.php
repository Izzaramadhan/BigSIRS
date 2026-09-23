<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\ProcedureCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_procedure_categories()
    {
        ProcedureCategory::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/master-data/procedure-categories');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_procedure_category()
    {
        $payload = [
            'name' => 'TINDAKAN KECIL',
            'description' => 'Tindakan medis kecil',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/master-data/procedure-categories', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'TINDAKAN KECIL');

        $this->assertDatabaseHas('procedure_categories', [
            'name' => 'TINDAKAN KECIL',
        ]);
    }

    public function test_normalizes_input_on_create()
    {
        $payload = [
            'name' => '  TINDAKAN    KECIL  ',
            'description' => '   ',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/master-data/procedure-categories', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'TINDAKAN KECIL')
            ->assertJsonPath('data.description', null);
    }

    public function test_can_update_procedure_category()
    {
        $category = ProcedureCategory::factory()->create();

        $payload = [
            'name' => 'TINDAKAN BESAR',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/master-data/procedure-categories/{$category->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'TINDAKAN BESAR');

        $this->assertDatabaseHas('procedure_categories', [
            'id' => $category->id,
            'name' => 'TINDAKAN BESAR',
        ]);
    }

    public function test_can_soft_delete_procedure_category()
    {
        $category = ProcedureCategory::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/master-data/procedure-categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('procedure_categories', ['id' => $category->id]);
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/v1/master-data/procedure-categories');
        $response->assertStatus(401);
    }
}
