<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LegacyRoomAndBedCrudTest extends TestCase
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

        DB::connection('legacy')->statement('
            CREATE TABLE ref_gedung (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_lantai (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_gedung INTEGER NULL,
                nama VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_bangsal (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_lantai INTEGER NULL,
                kode VARCHAR(10) NULL,
                nama VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_kelas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(50) NULL,
                kode VARCHAR(10) NULL,
                harga REAL DEFAULT 0,
                deskripsi VARCHAR(50) NULL,
                kode_aplicares VARCHAR(5) NULL,
                kode_sirs VARCHAR(100) NULL,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_kamar (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kode VARCHAR(10) NULL,
                id_bangsal INTEGER NULL,
                id_kelas INTEGER NULL,
                nama VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                total_bed INTEGER DEFAULT 0,
                total_bed_terpakai INTEGER DEFAULT 0,
                total_bed_kosong INTEGER DEFAULT 0,
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE ref_bed (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_kamar INTEGER NULL,
                nama VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                tersedia VARCHAR(20) DEFAULT "kosong",
                status CHAR(1) DEFAULT "1",
                id_user INTEGER NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ');

        DB::connection('legacy')->statement('
            CREATE TABLE tbl_user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) NULL,
                status CHAR(1) DEFAULT "1"
            )
        ');

        DB::connection('legacy')->table('tbl_user')->insert([
            'id' => 1,
            'username' => 'admin.legacy',
            'status' => '1',
        ]);

        $this->authUser = User::factory()->create([
            'username' => 'admin.legacy',
            'email' => 'admin.legacy@example.com',
        ]);
        Sanctum::actingAs($this->authUser);
    }

    protected function tearDown(): void
    {
        LegacyAccess::clearCache();
        parent::tearDown();
    }

    public function test_building_and_floor_and_ward_direct_crud(): void
    {
        // 1. Building CRUD
        $createBuilding = $this->postJson('/api/v1/master-data/buildings', [
            'name' => 'Gedung Teratai',
            'description' => 'Gedung Perawatan Teratai',
        ]);
        $createBuilding->assertStatus(201);
        $buildingId = $createBuilding->json('data.id');
        $this->assertNotNull($buildingId);
        $this->assertDatabaseHas('ref_gedung', ['id' => $buildingId, 'nama' => 'Gedung Teratai'], 'legacy');

        $updateBuilding = $this->putJson("/api/v1/master-data/buildings/{$buildingId}", [
            'name' => 'Gedung Teratai Utama',
            'description' => 'Gedung Perawatan Teratai Baru',
        ]);
        $updateBuilding->assertStatus(200);
        $this->assertDatabaseHas('ref_gedung', ['id' => $buildingId, 'nama' => 'Gedung Teratai Utama'], 'legacy');

        // 2. Floor CRUD
        $createFloor = $this->postJson('/api/v1/master-data/floors', [
            'building_id' => $buildingId,
            'name' => 'Lantai 1',
            'description' => 'Lantai Dasar Teratai',
        ]);
        $createFloor->assertStatus(201);
        $floorId = $createFloor->json('data.id');
        $this->assertNotNull($floorId);
        $this->assertDatabaseHas('ref_lantai', ['id' => $floorId, 'nama' => 'Lantai 1', 'id_gedung' => $buildingId], 'legacy');

        $updateFloor = $this->putJson("/api/v1/master-data/floors/{$floorId}", [
            'building_id' => $buildingId,
            'name' => 'Lantai 1 Sayap Barat',
            'description' => 'Lantai 1 Barat',
        ]);
        $updateFloor->assertStatus(200);
        $this->assertDatabaseHas('ref_lantai', ['id' => $floorId, 'nama' => 'Lantai 1 Sayap Barat'], 'legacy');

        // 3. Ward CRUD
        $createWard = $this->postJson('/api/v1/master-data/wards', [
            'floor_id' => $floorId,
            'name' => 'Bangsal Anak',
            'description' => 'Bangsal Rawat Anak',
        ]);
        $createWard->assertStatus(201);
        $wardId = $createWard->json('data.id');
        $this->assertNotNull($wardId);
        $this->assertDatabaseHas('ref_bangsal', ['id' => $wardId, 'nama' => 'Bangsal Anak', 'id_lantai' => $floorId], 'legacy');

        $updateWard = $this->putJson("/api/v1/master-data/wards/{$wardId}", [
            'floor_id' => $floorId,
            'name' => 'Bangsal Pediatri',
            'description' => 'Bangsal Pediatri Utama',
        ]);
        $updateWard->assertStatus(200);
        $this->assertDatabaseHas('ref_bangsal', ['id' => $wardId, 'nama' => 'Bangsal Pediatri'], 'legacy');

        // Clean up
        $this->deleteJson("/api/v1/master-data/wards/{$wardId}")->assertStatus(204);
        $this->deleteJson("/api/v1/master-data/floors/{$floorId}")->assertStatus(204);
        $this->deleteJson("/api/v1/master-data/buildings/{$buildingId}")->assertStatus(204);
    }

    public function test_room_class_and_room_and_bed_direct_crud(): void
    {
        // Seed building, floor, ward
        $buildingId = DB::connection('legacy')->table('ref_gedung')->insertGetId([
            'nama' => 'Gedung Melati',
            'status' => '1',
            'id_user' => 1,
        ]);
        $floorId = DB::connection('legacy')->table('ref_lantai')->insertGetId([
            'id_gedung' => $buildingId,
            'nama' => 'Lantai 2',
            'status' => '1',
            'id_user' => 1,
        ]);
        $wardId = DB::connection('legacy')->table('ref_bangsal')->insertGetId([
            'id_lantai' => $floorId,
            'nama' => 'Bangsal Bedah',
            'status' => '1',
            'id_user' => 1,
        ]);

        // 1. Room Class CRUD
        $createClass = $this->postJson('/api/v1/master-data/room-classes', [
            'code' => 'VIP-01',
            'name' => 'Kelas VIP Khusus',
            'price' => 500000,
            'description' => 'Kamar VIP dengan AC',
        ]);
        $createClass->assertStatus(201);
        $classId = $createClass->json('data.id');
        $this->assertNotNull($classId);
        $this->assertDatabaseHas('ref_kelas', ['id' => $classId, 'kode' => 'VIP-01', 'nama' => 'Kelas VIP Khusus'], 'legacy');

        $updateClass = $this->putJson("/api/v1/master-data/room-classes/{$classId}", [
            'code' => 'VIP-01-REV',
            'name' => 'Kelas VIP Spesial',
            'price' => 550000,
            'description' => 'Kamar VIP AC + TV',
        ]);
        $updateClass->assertStatus(200);
        $this->assertDatabaseHas('ref_kelas', ['id' => $classId, 'kode' => 'VIP-01-REV', 'nama' => 'Kelas VIP Spesial'], 'legacy');

        // 2. Room CRUD
        $createRoom = $this->postJson('/api/v1/master-data/rooms', [
            'ward_id' => $wardId,
            'room_class_id' => $classId,
            'code' => 'KMR-201',
            'name' => 'Kamar 201',
            'description' => 'Kamar Bedah VIP',
        ]);
        $createRoom->assertStatus(201);
        $roomId = $createRoom->json('data.id');
        $this->assertNotNull($roomId);
        $this->assertDatabaseHas('ref_kamar', ['id' => $roomId, 'kode' => 'KMR-201', 'nama' => 'Kamar 201'], 'legacy');

        $updateRoom = $this->putJson("/api/v1/master-data/rooms/{$roomId}", [
            'ward_id' => $wardId,
            'room_class_id' => $classId,
            'code' => 'KMR-201-REV',
            'name' => 'Kamar 201 Teratai',
            'description' => 'Kamar Bedah VIP Teratai',
        ]);
        $updateRoom->assertStatus(200);
        $this->assertDatabaseHas('ref_kamar', ['id' => $roomId, 'kode' => 'KMR-201-REV', 'nama' => 'Kamar 201 Teratai'], 'legacy');

        // 3. Bed CRUD
        $createBed = $this->postJson('/api/v1/master-data/beds', [
            'room_id' => $roomId,
            'name' => 'Bed 01',
            'description' => 'Bed Pasien VIP',
        ]);
        $createBed->assertStatus(201);
        $bedId = $createBed->json('data.id');
        $this->assertNotNull($bedId);
        $this->assertDatabaseHas('ref_bed', ['id' => $bedId, 'id_kamar' => $roomId, 'nama' => 'Bed 01'], 'legacy');

        $updateBed = $this->putJson("/api/v1/master-data/beds/{$bedId}", [
            'room_id' => $roomId,
            'name' => 'Bed 01 Elektrik',
            'description' => 'Bed Elektrik Pasien VIP',
        ]);
        $updateBed->assertStatus(200);
        $this->assertDatabaseHas('ref_bed', ['id' => $bedId, 'nama' => 'Bed 01 Elektrik'], 'legacy');

        // Clean up
        $this->deleteJson("/api/v1/master-data/beds/{$bedId}")->assertStatus(204);
        $this->deleteJson("/api/v1/master-data/rooms/{$roomId}")->assertStatus(204);
        $this->deleteJson("/api/v1/master-data/room-classes/{$classId}")->assertStatus(204);
    }
}

