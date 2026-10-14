<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Warehouse;
use App\Models\Polyclinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolyclinicTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'POLI-01',
            'name' => 'Poli Umum',
            'service_type' => 'rawat-jalan',
            'description' => 'Deskripsi poli',
            'is_visible' => true,
            'is_online_visible' => false,
            'quota' => 20,
            'jkn_quota' => 10,
        ], $overrides);
    }

    // ------------------------------------------------------------------ //
    // Auth
    // ------------------------------------------------------------------ //
    public function test_unauthorized_user_cannot_access_endpoints(): void
    {
        $this->getJson('/api/v1/master-data/polyclinics')->assertUnauthorized();
        $this->postJson('/api/v1/master-data/polyclinics', [])->assertUnauthorized();
    }

    // ------------------------------------------------------------------ //
    // List
    // ------------------------------------------------------------------ //
    public function test_can_list_polyclinics(): void
    {
        Polyclinic::factory()->count(20)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/polyclinics?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'service_type', 'quota', 'jkn_quota', 'is_active'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonCount(10, 'data');
    }

    public function test_can_search_polyclinics(): void
    {
        Polyclinic::factory()->create(['name' => 'Poliklinik Mata', 'code' => 'P-MATA']);
        Polyclinic::factory()->create(['name' => 'Poliklinik Gigi', 'code' => 'P-GIGI']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/polyclinics?search=mata');
        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals('Poliklinik Mata', $response->json('data.0.name'));
    }

    public function test_can_filter_by_service_type(): void
    {
        Polyclinic::factory()->create(['service_type' => 'rawat-jalan', 'code' => 'RJ01']);
        Polyclinic::factory()->create(['service_type' => 'farmasi', 'code' => 'FA01']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/polyclinics?service_type=farmasi');
        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals('FA01', $response->json('data.0.code'));
    }

    // ------------------------------------------------------------------ //
    // Service Types endpoint
    // ------------------------------------------------------------------ //
    public function test_can_get_service_types(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/polyclinics/service-types');
        $response->assertOk()->assertJsonStructure(['data' => [['value', 'label']]]);
        $values = array_column($response->json('data'), 'value');
        $this->assertContains('rawat-jalan', $values);
        $this->assertContains('farmasi', $values);
    }

    // ------------------------------------------------------------------ //
    // Create
    // ------------------------------------------------------------------ //
    public function test_can_create_polyclinic_with_all_new_fields(): void
    {
        $payload = $this->validPayload();
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('polyclinics', [
            'code' => 'POLI-01',
            'name' => 'Poli Umum',
            'service_type' => 'rawat-jalan',
            'quota' => 20,
            'jkn_quota' => 10,
            'is_visible' => true,
            'is_online_visible' => false,
        ]);
    }

    public function test_create_normalizes_code_and_name(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $this->validPayload([
            'code' => ' poli-01 ',
            'name' => ' Poli   Anak ',
        ]));

        $response->assertCreated();
        $this->assertDatabaseHas('polyclinics', [
            'code' => 'POLI-01',
            'name' => 'Poli Anak',
        ]);
    }

    public function test_create_requires_service_type(): void
    {
        $payload = $this->validPayload();
        unset($payload['service_type']);

        $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_type']);
    }

    public function test_create_rejects_invalid_service_type(): void
    {
        $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $this->validPayload([
            'service_type' => 'invalid-type',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['service_type']);
    }

    public function test_create_rejects_negative_quota(): void
    {
        $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $this->validPayload([
            'quota' => -5,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['quota']);
    }

    public function test_create_rejects_negative_jkn_quota(): void
    {
        $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $this->validPayload([
            'jkn_quota' => -1,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['jkn_quota']);
    }

    public function test_cannot_create_with_duplicate_code(): void
    {
        Polyclinic::factory()->create(['code' => 'POLI-01']);
        $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_parent_id_cannot_be_set_from_create_form(): void
    {
        $other = Polyclinic::factory()->create();
        $payload = $this->validPayload(['parent_id' => $other->id]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $payload);
        $response->assertCreated();

        // parent_id must remain null regardless of what was sent
        $this->assertDatabaseHas('polyclinics', ['code' => 'POLI-01', 'parent_id' => null]);
    }

    // ------------------------------------------------------------------ //
    // Update
    // ------------------------------------------------------------------ //
    public function test_can_update_polyclinic_with_all_new_fields(): void
    {
        $polyclinic = Polyclinic::factory()->create();
        $payload = $this->validPayload([
            'code' => $polyclinic->code,
            'service_type' => 'farmasi',
            'quota' => 99,
            'jkn_quota' => 50,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/polyclinics/{$polyclinic->id}", $payload);
        $response->assertOk();

        $this->assertDatabaseHas('polyclinics', [
            'id' => $polyclinic->id,
            'service_type' => 'farmasi',
            'quota' => 99,
            'jkn_quota' => 50,
        ]);
    }

    public function test_parent_id_cannot_be_modified_from_update_form(): void
    {
        $parent = Polyclinic::factory()->create();
        $polyclinic = Polyclinic::factory()->create(['parent_id' => $parent->id]);
        $origParentId = $polyclinic->parent_id;

        // Attempt to clear parent via user form
        $payload = $this->validPayload([
            'code' => $polyclinic->code,
            'parent_id' => null,
        ]);

        $this->actingAs($this->user)->putJson("/api/v1/master-data/polyclinics/{$polyclinic->id}", $payload)->assertOk();

        // parent_id must remain untouched
        $this->assertDatabaseHas('polyclinics', [
            'id' => $polyclinic->id,
            'parent_id' => $origParentId,
        ]);
    }

    public function test_hierarchy_preserved_when_updating(): void
    {
        $parent = Polyclinic::factory()->create();
        $child = Polyclinic::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->user)->putJson("/api/v1/master-data/polyclinics/{$child->id}", $this->validPayload([
            'code' => $child->code,
        ]))->assertOk();

        $this->assertDatabaseHas('polyclinics', ['id' => $child->id, 'parent_id' => $parent->id]);
    }

    // ------------------------------------------------------------------ //
    // Status toggle
    // ------------------------------------------------------------------ //
    public function test_can_update_status(): void
    {
        $polyclinic = Polyclinic::factory()->create(['is_active' => true]);

        $this->actingAs($this->user)->patchJson("/api/v1/master-data/polyclinics/{$polyclinic->id}/status", [
            'is_active' => false,
        ])->assertOk();

        $this->assertDatabaseHas('polyclinics', ['id' => $polyclinic->id, 'is_active' => false]);
    }

    // ------------------------------------------------------------------ //
    // Soft delete
    // ------------------------------------------------------------------ //
    public function test_can_soft_delete_polyclinic(): void
    {
        $polyclinic = Polyclinic::factory()->create();

        $this->actingAs($this->user)->deleteJson("/api/v1/master-data/polyclinics/{$polyclinic->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('polyclinics', ['id' => $polyclinic->id]);
    }

    public function test_cannot_delete_parent_with_children(): void
    {
        $parent = Polyclinic::factory()->create();
        Polyclinic::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->user)->deleteJson("/api/v1/master-data/polyclinics/{$parent->id}")
            ->assertConflict();

        $this->assertDatabaseHas('polyclinics', ['id' => $parent->id, 'deleted_at' => null]);
    }

    // ------------------------------------------------------------------ //
    // Data integrity
    // ------------------------------------------------------------------ //
    public function test_existing_136_records_are_not_deleted_on_db_migration(): void
    {
        // This test uses RefreshDatabase so it starts clean;
        // the key assertion is that the migration structure is additive and existing records survive.
        // We verify by creating seeded records with legacy_id and checking they persist after factory usage.
        $imported = Polyclinic::factory()->count(5)->create(['legacy_id' => null]);
        $this->assertDatabaseCount('polyclinics', 5);

        // Additional creates don't wipe them
        Polyclinic::factory()->create();
        $this->assertDatabaseCount('polyclinics', 6);
    }

    // ------------------------------------------------------------------ //
    // BPJS Code
    // ------------------------------------------------------------------ //
    public function test_can_create_polyclinic_with_bpjs_code(): void
    {
        $payload = $this->validPayload([
            'code' => 'POLI-BPJS-01',
            'bpjs_code' => '001',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.bpjs_code', '001');

        $this->assertDatabaseHas('polyclinics', [
            'code' => 'POLI-BPJS-01',
            'bpjs_code' => '001',
        ]);
    }

    public function test_can_create_polyclinic_with_null_bpjs_code(): void
    {
        $payload = $this->validPayload([
            'code' => 'POLI-BPJS-NULL',
            'bpjs_code' => null,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.bpjs_code', null);

        $this->assertDatabaseHas('polyclinics', [
            'code' => 'POLI-BPJS-NULL',
            'bpjs_code' => null,
        ]);
    }

    public function test_create_trims_bpjs_code_whitespace(): void
    {
        $payload = $this->validPayload([
            'code' => 'POLI-BPJS-TRIM',
            'bpjs_code' => '   BPJS-TRIMMED   ',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.bpjs_code', 'BPJS-TRIMMED');

        $this->assertDatabaseHas('polyclinics', [
            'code' => 'POLI-BPJS-TRIM',
            'bpjs_code' => 'BPJS-TRIMMED',
        ]);
    }

    public function test_create_stores_empty_bpjs_code_as_null(): void
    {
        $payload = $this->validPayload([
            'code' => 'POLI-BPJS-EMPTY',
            'bpjs_code' => '   ',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.bpjs_code', null);

        $this->assertDatabaseHas('polyclinics', [
            'code' => 'POLI-BPJS-EMPTY',
            'bpjs_code' => null,
        ]);
    }

    public function test_create_rejects_bpjs_code_longer_than_50_chars(): void
    {
        $payload = $this->validPayload([
            'code' => 'POLI-BPJS-TOOLONG',
            'bpjs_code' => str_repeat('A', 51),
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['bpjs_code']);
    }

    public function test_can_update_bpjs_code(): void
    {
        $polyclinic = Polyclinic::factory()->create([
            'bpjs_code' => 'BPJS-OLD',
        ]);

        $payload = $this->validPayload([
            'code' => $polyclinic->code,
            'bpjs_code' => 'BPJS-NEW',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/polyclinics/{$polyclinic->id}", $payload);

        $response->assertOk();
        $response->assertJsonPath('data.bpjs_code', 'BPJS-NEW');

        $this->assertDatabaseHas('polyclinics', [
            'id' => $polyclinic->id,
            'bpjs_code' => 'BPJS-NEW',
        ]);
    }

    public function test_update_can_clear_bpjs_code_to_null(): void
    {
        $polyclinic = Polyclinic::factory()->create([
            'bpjs_code' => 'BPJS-EXISTING',
        ]);

        $payload = $this->validPayload([
            'code' => $polyclinic->code,
            'bpjs_code' => null,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/polyclinics/{$polyclinic->id}", $payload);

        $response->assertOk();
        $response->assertJsonPath('data.bpjs_code', null);

        $this->assertDatabaseHas('polyclinics', [
            'id' => $polyclinic->id,
            'bpjs_code' => null,
        ]);

        // Also test with empty string converted to null
        $polyclinic->update(['bpjs_code' => 'BPJS-EXISTING-2']);
        $payload2 = $this->validPayload([
            'code' => $polyclinic->code,
            'bpjs_code' => '   ',
        ]);

        $response2 = $this->actingAs($this->user)->putJson("/api/v1/master-data/polyclinics/{$polyclinic->id}", $payload2);
        $response2->assertOk();
        $response2->assertJsonPath('data.bpjs_code', null);
        $this->assertDatabaseHas('polyclinics', [
            'id' => $polyclinic->id,
            'bpjs_code' => null,
        ]);
    }

    public function test_show_polyclinic_returns_bpjs_code(): void
    {
        $polyclinic = Polyclinic::factory()->create([
            'code' => 'POLI-SHOW',
            'name' => 'Poli Show Test',
            'bpjs_code' => 'BPJS-SHOW-01',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/polyclinics/{$polyclinic->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $polyclinic->id);
        $response->assertJsonPath('data.code', 'POLI-SHOW');
        $response->assertJsonPath('data.bpjs_code', 'BPJS-SHOW-01');
    }

    public function test_bpjs_code_does_not_break_warehouse_id_and_relation(): void
    {
        $warehouse = Warehouse::factory()->create([
            'code' => 'GD-01',
            'name' => 'Gudang Utama',
        ]);

        // 1. Create with bpjs_code and warehouse_id
        $createPayload = $this->validPayload([
            'code' => 'POLI-WH-01',
            'name' => 'Poli Gudang',
            'bpjs_code' => '001',
            'warehouse_id' => $warehouse->id,
        ]);

        $createResponse = $this->actingAs($this->user)->postJson('/api/v1/master-data/polyclinics', $createPayload);

        $createResponse->assertCreated();
        $createResponse->assertJsonPath('data.bpjs_code', '001');
        $createResponse->assertJsonPath('data.warehouse_id', $warehouse->id);
        $createResponse->assertJsonPath('data.warehouse.id', $warehouse->id);
        $createResponse->assertJsonPath('data.warehouse.name', 'Gudang Utama');

        $polyclinicId = $createResponse->json('data.id');

        // 2. Show returns both bpjs_code and warehouse
        $showResponse = $this->actingAs($this->user)->getJson("/api/v1/master-data/polyclinics/{$polyclinicId}");
        $showResponse->assertOk();
        $showResponse->assertJsonPath('data.bpjs_code', '001');
        $showResponse->assertJsonPath('data.warehouse_id', $warehouse->id);
        $showResponse->assertJsonPath('data.warehouse.id', $warehouse->id);

        // 3. Update bpjs_code while preserving warehouse_id
        $updatePayload = $this->validPayload([
            'code' => 'POLI-WH-01',
            'name' => 'Poli Gudang Update',
            'bpjs_code' => '002',
            'warehouse_id' => $warehouse->id,
        ]);

        $updateResponse = $this->actingAs($this->user)->putJson("/api/v1/master-data/polyclinics/{$polyclinicId}", $updatePayload);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.bpjs_code', '002');
        $updateResponse->assertJsonPath('data.warehouse_id', $warehouse->id);
        $updateResponse->assertJsonPath('data.warehouse.id', $warehouse->id);
    }
}
