<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Legacy\LegacyIcd10;
use App\Models\Legacy\LegacyMedicineRoute;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyMedicineRouteCrudTest extends TestCase
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

        // 1. Create legacy ref_obat_jalur_masuk table
        DB::connection('legacy')->statement('
            CREATE TABLE ref_obat_jalur_masuk (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                rute_obat VARCHAR(50) NOT NULL,
                obj_id VARCHAR(15) NULL,
                status INTEGER DEFAULT 1,
                create_time DATETIME,
                update_time DATETIME,
                delete_time DATETIME,
                user_id INTEGER NULL
            )
        ');

        // 2. Create legacy tbl_user table
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
                id_obat_jalur_masuk INTEGER NULL,
                status VARCHAR(1) DEFAULT "1",
                deleted_at DATETIME
            )
        ');

        // Insert legacy user
        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 103,
            'username' => 'admin',
            'nama' => 'Administrator Farmasi',
            'status' => '1',
        ]);

        $this->authUser = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@simrs.test',
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/master-data/medicine-routes');
        $response->assertStatus(401);
    }

    public function test_unmapped_legacy_user_is_forbidden_on_write(): void
    {
        $unmappedUser = User::factory()->create([
            'username' => 'unmapped_user',
        ]);
        Sanctum::actingAs($unmappedUser);

        $response = $this->postJson('/api/v1/master-data/medicine-routes', [
            'name' => 'Intramuskular',
        ]);

        $response->assertStatus(403);
    }

    public function test_can_list_and_search_routes_directly_from_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        DB::connection('legacy')->table('ref_obat_jalur_masuk')->insert([
            ['id' => 1, 'rute_obat' => 'Oral', 'obj_id' => 'RUT-01', 'status' => 1, 'create_time' => now()],
            ['id' => 2, 'rute_obat' => 'Intravena', 'obj_id' => 'RUT-02', 'status' => 1, 'create_time' => now()],
            ['id' => 3, 'rute_obat' => 'Topikal', 'obj_id' => 'RUT-03', 'status' => 0, 'create_time' => now()],
        ]);

        $response = $this->getJson('/api/v1/master-data/medicine-routes');
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));

        // Search
        $searchResponse = $this->getJson('/api/v1/master-data/medicine-routes?search=Intra');
        $searchResponse->assertStatus(200);
        $this->assertCount(1, $searchResponse->json('data'));
        $this->assertEquals('Intravena', $searchResponse->json('data.0.name'));
    }

    public function test_can_show_single_route_directly_from_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_obat_jalur_masuk')->insertGetId([
            'rute_obat' => 'Subkutan',
            'obj_id' => 'RUT-SC',
            'status' => 1,
            'create_time' => now(),
        ]);

        $response = $this->getJson("/api/v1/master-data/medicine-routes/{$id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Subkutan')
            ->assertJsonPath('data.object_code', 'RUT-SC');
    }

    public function test_can_create_route_directly_in_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $payload = [
            'name' => 'Inhalasi',
            'object_code' => 'RUT-INH',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/master-data/medicine-routes', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Inhalasi')
            ->assertJsonPath('data.object_code', 'RUT-INH');

        $createdId = $response->json('data.id');
        $row = DB::connection('legacy')->table('ref_obat_jalur_masuk')->where('id', $createdId)->first();
        $this->assertNotNull($row);
        $this->assertEquals('Inhalasi', $row->rute_obat);
        $this->assertEquals('RUT-INH', $row->obj_id);
        $this->assertEquals(103, $row->user_id);

        $this->assertDatabaseCount('medicine_routes', 0);
    }

    public function test_can_update_route_directly_in_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_obat_jalur_masuk')->insertGetId([
            'rute_obat' => 'Rute Lama',
            'obj_id' => 'RUT-OLD',
            'status' => 1,
            'create_time' => now(),
        ]);

        $payload = [
            'name' => 'Rute Baru',
            'object_code' => 'RUT-NEW',
            'is_active' => true,
        ];

        $response = $this->putJson("/api/v1/master-data/medicine-routes/{$id}", $payload);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Rute Baru');

        $row = DB::connection('legacy')->table('ref_obat_jalur_masuk')->where('id', $id)->first();
        $this->assertEquals('Rute Baru', $row->rute_obat);
        $this->assertEquals('RUT-NEW', $row->obj_id);
        $this->assertEquals(103, $row->user_id);
        $this->assertNotNull($row->update_time);

        $this->assertDatabaseCount('medicine_routes', 0);
    }

    public function test_can_toggle_route_status_in_legacy_db(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_obat_jalur_masuk')->insertGetId([
            'rute_obat' => 'Rute Aktif',
            'status' => 1,
            'create_time' => now(),
        ]);

        $response = $this->patchJson("/api/v1/master-data/medicine-routes/{$id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $row = DB::connection('legacy')->table('ref_obat_jalur_masuk')->where('id', $id)->first();
        $this->assertEquals(0, $row->status);
        $this->assertEquals(103, $row->user_id);
    }

    public function test_can_soft_delete_unreferenced_route(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_obat_jalur_masuk')->insertGetId([
            'rute_obat' => 'Rute Hapus',
            'status' => 1,
            'create_time' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/master-data/medicine-routes/{$id}");
        $response->assertStatus(204);

        $row = DB::connection('legacy')->table('ref_obat_jalur_masuk')->where('id', $id)->first();
        $this->assertNotNull($row->delete_time);
        $this->assertEquals(0, $row->status);
        $this->assertEquals(103, $row->user_id);
    }

    public function test_cannot_delete_route_referenced_in_medicines(): void
    {
        Sanctum::actingAs($this->authUser);

        $id = DB::connection('legacy')->table('ref_obat_jalur_masuk')->insertGetId([
            'rute_obat' => 'Rute Terpakai',
            'status' => 1,
            'create_time' => now(),
        ]);

        DB::connection('legacy')->table('ref_obat')->insert([
            'nama' => 'Obat Pengguna Rute',
            'id_obat_jalur_masuk' => $id,
            'status' => '1',
        ]);

        $response = $this->deleteJson("/api/v1/master-data/medicine-routes/{$id}");
        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Jalur masuk obat tidak dapat dihapus karena masih digunakan pada data obat.',
            ]);

        $row = DB::connection('legacy')->table('ref_obat_jalur_masuk')->where('id', $id)->first();
        $this->assertNull($row->delete_time);
        $this->assertEquals(1, $row->status);
    }

    public function test_other_legacy_models_remain_read_only(): void
    {
        $spec = new \App\Models\Legacy\LegacySpecialization();
        $spec->nama_spesialisasi = 'Spesialis Test Write';

        $this->expectException(\RuntimeException::class);
        $spec->save();
    }
}
