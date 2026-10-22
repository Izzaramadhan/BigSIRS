<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_cannot_access_buildings(): void
    {
        $this->getJson('/api/v1/master-data/buildings')->assertUnauthorized();
        $this->postJson('/api/v1/master-data/buildings', ['name' => 'Gedung A'])->assertUnauthorized();
        $this->getJson('/api/v1/master-data/buildings/1')->assertUnauthorized();
        $this->putJson('/api/v1/master-data/buildings/1', ['name' => 'Gedung A'])->assertUnauthorized();
        $this->deleteJson('/api/v1/master-data/buildings/1')->assertUnauthorized();
    }

    public function test_can_list_buildings_with_pagination(): void
    {
        Building::factory()->count(15)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/buildings?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'created_at', 'updated_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links',
            ]);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(15, $response->json('meta.total'));
    }

    public function test_can_search_buildings_by_name(): void
    {
        Building::factory()->create(['name' => 'Gedung Teratai', 'description' => 'Ruang Operasi']);
        Building::factory()->create(['name' => 'Gedung Mawar', 'description' => 'Ruang Rawat Inap']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/buildings?search=Teratai');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gedung Teratai');
    }

    public function test_can_search_buildings_by_description(): void
    {
        Building::factory()->create(['name' => 'Gedung A', 'description' => 'Khusus Perawatan Anak']);
        Building::factory()->create(['name' => 'Gedung B', 'description' => 'Instalasi Gawat Darurat']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/buildings?search=Darurat');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gedung B');
    }

    public function test_can_show_building(): void
    {
        $building = Building::factory()->create([
            'name' => 'Gedung Paviliun',
            'description' => 'Gedung VIP dan VVIP',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/buildings/{$building->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $building->id)
            ->assertJsonPath('data.name', 'Gedung Paviliun')
            ->assertJsonPath('data.description', 'Gedung VIP dan VVIP');
    }

    public function test_can_create_building(): void
    {
        $payload = [
            'name' => 'Gedung Cempaka',
            'description' => 'Gedung Perawatan Penyakit Dalam',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/buildings', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Gedung Cempaka')
            ->assertJsonPath('data.description', 'Gedung Perawatan Penyakit Dalam');

        $this->assertDatabaseHas('buildings', [
            'name' => 'Gedung Cempaka',
            'description' => 'Gedung Perawatan Penyakit Dalam',
            'deleted_at' => null,
        ]);
    }

    public function test_create_building_trims_excessive_whitespace(): void
    {
        $payload = [
            'name' => "  Gedung    Anggrek   Utama  \n",
            'description' => '  Deskripsi dengan spasi depan belakang   ',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/buildings', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Gedung Anggrek Utama')
            ->assertJsonPath('data.description', 'Deskripsi dengan spasi depan belakang');

        $this->assertDatabaseHas('buildings', [
            'name' => 'Gedung Anggrek Utama',
            'description' => 'Deskripsi dengan spasi depan belakang',
        ]);
    }

    public function test_validation_name_is_required_to_create_building(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/buildings', [
            'name' => '',
            'description' => 'Hanya deskripsi',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validation_prevents_duplicate_building_name(): void
    {
        Building::factory()->create(['name' => 'Gedung Tulip']);

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/buildings', [
            'name' => 'Gedung Tulip',
            'description' => 'Deskripsi lain',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_create_building_with_same_name_as_soft_deleted_building(): void
    {
        $deleted = Building::factory()->create(['name' => 'Gedung Dahlia']);
        $deleted->delete();

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/buildings', [
            'name' => 'Gedung Dahlia',
            'description' => 'Gedung baru setelah gedung lama dihapus',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Gedung Dahlia');

        $this->assertEquals(2, Building::withTrashed()->where('name', 'Gedung Dahlia')->count());
    }

    public function test_can_update_building(): void
    {
        $building = Building::factory()->create([
            'name' => 'Gedung Flamboyan Lama',
            'description' => 'Deskripsi lama',
        ]);

        $payload = [
            'name' => 'Gedung Flamboyan Baru',
            'description' => 'Deskripsi baru',
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/buildings/{$building->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.id', $building->id)
            ->assertJsonPath('data.name', 'Gedung Flamboyan Baru')
            ->assertJsonPath('data.description', 'Deskripsi baru');

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'name' => 'Gedung Flamboyan Baru',
            'description' => 'Deskripsi baru',
        ]);
    }

    public function test_can_update_building_keeping_same_name(): void
    {
        $building = Building::factory()->create([
            'name' => 'Gedung Kenanga',
            'description' => 'Deskripsi awal',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/buildings/{$building->id}", [
            'name' => 'Gedung Kenanga',
            'description' => 'Deskripsi diperbarui',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Gedung Kenanga')
            ->assertJsonPath('data.description', 'Deskripsi diperbarui');
    }

    public function test_validation_prevents_duplicate_building_name_on_update(): void
    {
        Building::factory()->create(['name' => 'Gedung Alpha']);
        $buildingB = Building::factory()->create(['name' => 'Gedung Beta']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/buildings/{$buildingB->id}", [
            'name' => 'Gedung Alpha',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_delete_building_using_soft_delete(): void
    {
        $building = Building::factory()->create(['name' => 'Gedung Akan Dihapus']);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/buildings/{$building->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('buildings', [
            'id' => $building->id,
            'name' => 'Gedung Akan Dihapus',
        ]);
    }
}
