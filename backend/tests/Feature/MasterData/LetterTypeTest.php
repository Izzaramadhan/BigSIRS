<?php

namespace Tests\Feature\MasterData;

use App\Models\LetterType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTypeTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_letter_types_list()
    {
        LetterType::factory()->count(15)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/letter-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'legacy_resource', 'is_active', 'created_at']
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
            ]);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_can_search_letter_types()
    {
        LetterType::factory()->create(['name' => 'Surat Keterangan Sehat']);
        LetterType::factory()->create(['name' => 'Surat Rujukan']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/letter-types?search=Sehat');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Surat Keterangan Sehat', $response->json('data.0.name'));
    }

    public function test_can_create_letter_type()
    {
        $payload = [
            'name' => 'Surat Izin',
            'description' => 'Surat untuk izin cuti',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/letter-types', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Surat Izin')
            ->assertJsonPath('data.description', 'Surat untuk izin cuti');

        $this->assertDatabaseHas('letter_types', [
            'name' => 'Surat Izin',
            'description' => 'Surat untuk izin cuti',
            'is_active' => 1,
        ]);
    }

    public function test_cannot_create_with_duplicate_name()
    {
        LetterType::factory()->create(['name' => 'Surat Sehat']);

        $payload = [
            'name' => 'Surat Sehat',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/letter-types', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_letter_type()
    {
        $letterType = LetterType::factory()->create([
            'name' => 'Surat Awal',
        ]);

        $payload = [
            'name' => 'Surat Baru',
            'description' => 'Deskripsi baru',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/master-data/letter-types/{$letterType->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Surat Baru');

        $this->assertDatabaseHas('letter_types', [
            'id' => $letterType->id,
            'name' => 'Surat Baru',
            'description' => 'Deskripsi baru',
            'is_active' => 0,
        ]);
    }

    public function test_can_update_letter_type_status()
    {
        $letterType = LetterType::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/master-data/letter-types/{$letterType->id}/status", [
                'is_active' => false,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('letter_types', [
            'id' => $letterType->id,
            'is_active' => 0,
        ]);
    }

    public function test_can_delete_letter_type()
    {
        $letterType = LetterType::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/master-data/letter-types/{$letterType->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('letter_types', [
            'id' => $letterType->id,
        ]);
    }
}
