<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Legacy\LegacyIcd10;
use App\Models\Legacy\LegacyGenericMedicine;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyGenericMedicineCrudTest extends TestCase
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

        // 1. Create legacy ref_generik table
        DB::connection('legacy')->statement('
            CREATE TABLE ref_generik (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_hlp INTEGER NULL,
                nama_objek VARCHAR(255) NOT NULL,
                sediaan VARCHAR(255) NULL,
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

        // 3. Create legacy ref_obat table for referential checks
        DB::connection('legacy')->statement('
            CREATE TABLE ref_obat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(255) NOT NULL,
                id_generik INTEGER NULL,
                status VARCHAR(1) DEFAULT "1",
                deleted_at DATETIME NULL
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
        $this->getJson('/api/v1/master-data/generic-medicines')->assertStatus(401);
        $this->postJson('/api/v1/master-data/generic-medicines', ['name' => 'Paracetamol'])->assertStatus(401);
        $this->putJson('/api/v1/master-data/generic-medicines/1', ['name' => 'Paracetamol'])->assertStatus(401);
        $this->deleteJson('/api/v1/master-data/generic-medicines/1')->assertStatus(401);
    }

    public function test_unmapped_legacy_user_is_forbidden_on_write(): void
    {
        $unmappedUser = User::factory()->create([
            'username' => 'unmapped_user',
        ]);
        Sanctum::actingAs($unmappedUser);

        $id = DB::connection('legacy')->table('ref_generik')->insertGetId([
            'nama_objek' => 'Existing Generic',
            'status' => 1,
            'created_at' => now(),
        ]);

        $this->postJson('/api/v1/master-data/generic-medicines', [
            'name' => 'New Generic',
        ])->assertStatus(403);

        $this->putJson("/api/v1/master-data/generic-medicines/{$id}", [
            'name' => 'Updated Generic',
        ])->assertStatus(403);

        $this->patchJson("/api/v1/master-data/generic-medicines/{$id}/status", [
            'is_active' => false,
        ])->assertStatus(403);

        $this->deleteJson("/api/v1/master-data/generic-medicines/{$id}")->assertStatus(403);
    }

    public function test_can_list_and_search_generic_medicines_directly_from_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        DB::connection('legacy')->table('ref_generik')->insert([
            ['nama_objek' => 'Amoxicillin Trihydrate', 'status' => 1, 'created_at' => now()],
            ['nama_objek' => 'Ampicillin', 'status' => 1, 'created_at' => now()],
            ['nama_objek' => 'Paracetamol', 'status' => 0, 'created_at' => now()],
        ]);

        $response = $this->getJson('/api/v1/master-data/generic-medicines');
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $searchResponse = $this->getJson('/api/v1/master-data/generic-medicines?search=Amoxi');
        $searchResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Amoxicillin Trihydrate');

        $activeResponse = $this->getJson('/api/v1/master-data/generic-medicines?is_active=1');
        $activeResponse->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_show_single_generic_medicine_directly_from_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_generik')->insertGetId([
            'nama_objek' => 'Ibuprofen',
            'status' => 1,
            'created_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/master-data/generic-medicines/{$id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Ibuprofen')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_can_create_generic_medicine_directly_in_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'Cefixime Trihydrate',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/master-data/generic-medicines', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Cefixime Trihydrate')
            ->assertJsonPath('data.is_active', true);

        $createdId = $response->json('data.id');

        $row = DB::connection('legacy')->table('ref_generik')->where('id', $createdId)->first();
        $this->assertNotNull($row);
        $this->assertEquals('Cefixime Trihydrate', $row->nama_objek);
        $this->assertEquals(1, $row->status);
        $this->assertEquals(99, $row->id_user);
        $this->assertNotNull($row->created_at);

        // Ensure bigsirs_dev generic_medicines has 0 records
        $this->assertDatabaseCount('generic_medicines', 0);
    }

    public function test_rejects_duplicate_generic_name_case_insensitively(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_generik')->insertGetId([
            'nama_objek' => 'Metformin HCl',
            'status' => 1,
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/master-data/generic-medicines', [
            'name' => '  metformin hcl  ',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_generic_medicine_directly_in_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_generik')->insertGetId([
            'nama_objek' => 'Old Generic Name',
            'status' => 1,
            'created_at' => now(),
        ]);

        $response = $this->putJson("/api/v1/master-data/generic-medicines/{$id}", [
            'name' => 'Updated Generic Name',
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Generic Name')
            ->assertJsonPath('data.is_active', false);

        $row = DB::connection('legacy')->table('ref_generik')->where('id', $id)->first();
        $this->assertEquals('Updated Generic Name', $row->nama_objek);
        $this->assertEquals(0, $row->status);
        $this->assertEquals(99, $row->id_user);
        $this->assertNotNull($row->updated_at);

        $this->assertDatabaseCount('generic_medicines', 0);
    }

    public function test_can_toggle_generic_medicine_status_in_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_generik')->insertGetId([
            'nama_objek' => 'Antasida DOEN',
            'status' => 1,
            'created_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/master-data/generic-medicines/{$id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $row = DB::connection('legacy')->table('ref_generik')->where('id', $id)->first();
        $this->assertEquals(0, $row->status);
        $this->assertEquals(99, $row->id_user);
    }

    public function test_can_soft_delete_unreferenced_generic_medicine(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_generik')->insertGetId([
            'nama_objek' => 'Deletable Generic',
            'status' => 1,
            'created_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/master-data/generic-medicines/{$id}");
        $response->assertStatus(204);

        $row = DB::connection('legacy')->table('ref_generik')->where('id', $id)->first();
        $this->assertNotNull($row->deleted_at);
        $this->assertEquals(0, $row->status);
        $this->assertEquals(99, $row->id_user);
    }

    public function test_cannot_delete_generic_medicine_referenced_in_medicines(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_generik')->insertGetId([
            'nama_objek' => 'Referenced Generic',
            'status' => 1,
            'created_at' => now(),
        ]);

        // Add reference in ref_obat
        DB::connection('legacy')->table('ref_obat')->insert([
            'nama' => 'Medicine with Generic',
            'id_generik' => $id,
            'status' => '1',
        ]);

        $response = $this->deleteJson("/api/v1/master-data/generic-medicines/{$id}");
        $response->assertStatus(409)
            ->assertJson(['message' => 'Data generik tidak dapat dihapus karena masih digunakan pada data obat.']);

        $row = DB::connection('legacy')->table('ref_generik')->where('id', $id)->first();
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
