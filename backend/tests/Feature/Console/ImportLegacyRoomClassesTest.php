<?php

namespace Tests\Feature\Console;

use App\Models\MasterData\Room;
use App\Models\MasterData\RoomClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyRoomClassesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::connection('legacy')->statement('
            CREATE TABLE ref_kelas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama VARCHAR(50),
                deskripsi VARCHAR(50),
                harga DECIMAL(10,0),
                status VARCHAR(1) DEFAULT "1",
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER,
                kode_aplicares VARCHAR(5),
                kode VARCHAR(10),
                kode_sirs VARCHAR(100)
            )
        ');
    }

    public function test_can_import_active_room_classes_from_legacy(): void
    {
        DB::connection('legacy')->table('ref_kelas')->insert([
            [
                'id' => 1,
                'nama' => 'Kelas 1',
                'deskripsi' => '-',
                'harga' => 100000,
                'status' => '1',
                'created_at' => '2018-07-20 09:14:24',
                'updated_at' => '2025-01-30 11:29:51',
                'deleted_at' => '0000-00-00 00:00:00',
                'id_user' => 1,
                'kode_aplicares' => 'KL1',
                'kode' => null,
                'kode_sirs' => '3',
            ],
            [
                'id' => 4,
                'nama' => 'VIP',
                'deskripsi' => 'VIP Class',
                'harga' => 400000,
                'status' => '1',
                'created_at' => '2018-07-20 09:15:23',
                'updated_at' => '2025-01-30 11:29:51',
                'deleted_at' => '0000-00-00 00:00:00',
                'id_user' => 1,
                'kode_aplicares' => 'VIP',
                'kode' => null,
                'kode_sirs' => '2',
            ],
        ]);

        $this->artisan('legacy:import-room-classes')
            ->assertSuccessful();

        $this->assertDatabaseHas('room_classes', [
            'legacy_id' => 1,
            'name' => 'Kelas 1',
            'code' => 'KL1',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('room_classes', [
            'legacy_id' => 4,
            'name' => 'VIP',
            'code' => 'VIP',
            'description' => 'VIP Class',
            'deleted_at' => null,
        ]);
    }

    public function test_import_maps_existing_rooms_to_room_classes(): void
    {
        $room = Room::factory()->create([
            'class_id' => 1,
            'room_class_id' => null,
        ]);

        DB::connection('legacy')->table('ref_kelas')->insert([
            [
                'id' => 1,
                'nama' => 'Kelas 1',
                'deskripsi' => '-',
                'harga' => 100000,
                'status' => '1',
                'created_at' => '2018-07-20 09:14:24',
                'updated_at' => '2025-01-30 11:29:51',
                'deleted_at' => '0000-00-00 00:00:00',
                'id_user' => 1,
                'kode_aplicares' => 'KL1',
                'kode' => null,
                'kode_sirs' => '3',
            ],
        ]);

        $this->artisan('legacy:import-room-classes')
            ->assertSuccessful();

        $importedClass = RoomClass::where('legacy_id', 1)->first();
        $this->assertNotNull($importedClass);

        $room->refresh();
        $this->assertEquals($importedClass->id, $room->room_class_id);
    }
}
