<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Legacy\LegacyIcd10;
use App\Models\Legacy\LegacyMedicationSigna;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyMedicationSignaCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        LegacyAccess::clearCache();

        // 1. Create legacy ref_signa table
        DB::connection('legacy')->statement('
            CREATE TABLE ref_signa (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NOT NULL,
                status INTEGER DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER NULL
            )
        ');

        // 2. Create legacy tbl_user table for identity mapping
        DB::connection('legacy')->statement('
            CREATE TABLE tbl_user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(40) NOT NULL,
                nama VARCHAR(100),
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                id_user INTEGER NULL
            )
        ');

        // 3. Create legacy trx_resep_detail table for referential checks
        DB::connection('legacy')->statement('
            CREATE TABLE trx_resep_detail (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                signa VARCHAR(255) NULL
            )
        ');

        // Create mapped user in legacy tbl_user
        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 99,
            'username' => 'apoteker_user',
            'nama' => 'Apoteker User',
            'status' => '1',
            'created_at' => now(),
        ]);

        $this->authUser = User::factory()->create([
            'username' => 'apoteker_user',
            'name' => 'Apoteker User',
        ]);
    }

    protected function tearDown(): void
    {
        LegacyAccess::clearCache();
        parent::tearDown();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/master-data/medication-signas')->assertStatus(401);
        $this->postJson('/api/v1/master-data/medication-signas', ['name' => '1x sehari'])->assertStatus(401);
        $this->putJson('/api/v1/master-data/medication-signas/1', ['name' => '1x sehari'])->assertStatus(401);
        $this->deleteJson('/api/v1/master-data/medication-signas/1')->assertStatus(401);
    }

    public function test_unmapped_legacy_user_is_forbidden_on_write(): void
    {
        $unmappedUser = User::factory()->create([
            'username' => 'unmapped_user',
        ]);
        Sanctum::actingAs($unmappedUser);

        $id = DB::connection('legacy')->table('ref_signa')->insertGetId([
            'nama' => 'Existing Signa',
            'status' => 1,
            'created_at' => now(),
        ]);

        $this->postJson('/api/v1/master-data/medication-signas', [
            'name' => 'New Signa',
        ])->assertStatus(403);

        $this->putJson("/api/v1/master-data/medication-signas/{$id}", [
            'name' => 'Updated Signa',
        ])->assertStatus(403);

        $this->patchJson("/api/v1/master-data/medication-signas/{$id}/status", [
            'is_active' => false,
        ])->assertStatus(403);

        $this->deleteJson("/api/v1/master-data/medication-signas/{$id}")->assertStatus(403);
    }

    public function test_can_list_and_search_medication_signas_directly_from_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        DB::connection('legacy')->table('ref_signa')->insert([
            ['nama' => '3 x sehari 1 tablet sesudah makan', 'status' => 1, 'created_at' => now()],
            ['nama' => '1 x sehari 1 sendok teh sebelum makan', 'status' => 1, 'created_at' => now()],
            ['nama' => '2 x sehari 1 kapsul', 'status' => 0, 'created_at' => now()],
        ]);

        $response = $this->getJson('/api/v1/master-data/medication-signas');
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $searchResponse = $this->getJson('/api/v1/master-data/medication-signas?search=sendok');
        $searchResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', '1 x sehari 1 sendok teh sebelum makan');

        $activeResponse = $this->getJson('/api/v1/master-data/medication-signas?is_active=1');
        $activeResponse->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_show_single_medication_signa_directly_from_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_signa')->insertGetId([
            'nama' => '1 x sehari pada malam hari',
            'status' => 1,
            'created_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/master-data/medication-signas/{$id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', '1 x sehari pada malam hari')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_can_create_medication_signa_directly_in_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => '4 x sehari 1 sendok takar',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/master-data/medication-signas', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', '4 x sehari 1 sendok takar')
            ->assertJsonPath('data.is_active', true);

        $createdId = $response->json('data.id');

        $row = DB::connection('legacy')->table('ref_signa')->where('id', $createdId)->first();
        $this->assertNotNull($row);
        $this->assertEquals('4 x sehari 1 sendok takar', $row->nama);
        $this->assertEquals(1, $row->status);
        $this->assertEquals(99, $row->id_user);
        $this->assertNotNull($row->created_at);

        // Ensure bigsirs_dev medication_signas has 0 records
        $this->assertDatabaseCount('medication_signas', 0);
    }

    public function test_can_update_medication_signa_directly_in_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_signa')->insertGetId([
            'nama' => 'Old Signa',
            'status' => 1,
            'created_at' => now(),
        ]);

        $response = $this->putJson("/api/v1/master-data/medication-signas/{$id}", [
            'name' => 'Updated Signa Description',
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Signa Description')
            ->assertJsonPath('data.is_active', false);

        $row = DB::connection('legacy')->table('ref_signa')->where('id', $id)->first();
        $this->assertEquals('Updated Signa Description', $row->nama);
        $this->assertEquals(0, $row->status);
        $this->assertEquals(99, $row->id_user);
        $this->assertNotNull($row->updated_at);

        $this->assertDatabaseCount('medication_signas', 0);
    }

    public function test_can_toggle_medication_signa_status_in_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_signa')->insertGetId([
            'nama' => 'Toggled Signa',
            'status' => 1,
            'created_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/master-data/medication-signas/{$id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $row = DB::connection('legacy')->table('ref_signa')->where('id', $id)->first();
        $this->assertEquals(0, $row->status);
        $this->assertEquals(99, $row->id_user);
    }

    public function test_can_soft_delete_unreferenced_medication_signa(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_signa')->insertGetId([
            'nama' => 'Deletable Signa',
            'status' => 1,
            'created_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/master-data/medication-signas/{$id}");
        $response->assertStatus(204);

        $row = DB::connection('legacy')->table('ref_signa')->where('id', $id)->first();
        $this->assertNotNull($row->deleted_at);
        $this->assertEquals(0, $row->status);
        $this->assertEquals(99, $row->id_user);
    }

    public function test_cannot_delete_medication_signa_referenced_in_prescriptions(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_signa')->insertGetId([
            'nama' => '3 x sehari 1 tablet',
            'status' => 1,
            'created_at' => now(),
        ]);

        // Add reference in trx_resep_detail
        DB::connection('legacy')->table('trx_resep_detail')->insert([
            'signa' => '3 x sehari 1 tablet',
        ]);

        $response = $this->deleteJson("/api/v1/master-data/medication-signas/{$id}");
        $response->assertStatus(409)
            ->assertJson(['message' => 'Signa obat tidak dapat dihapus karena masih digunakan pada data resep obat.']);

        $row = DB::connection('legacy')->table('ref_signa')->where('id', $id)->first();
        $this->assertNull($row->deleted_at);
        $this->assertEquals(1, $row->status);
    }

    public function test_other_legacy_models_remain_read_only(): void
    {
        $spec = new \App\Models\Legacy\LegacySpecialization();
        $this->assertTrue($spec->isReadOnly());

        $this->expectException(\RuntimeException::class);
        $spec->save();
    }
}

