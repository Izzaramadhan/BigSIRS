<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\MasterData\MedicationSigna;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MedicationSignaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(function ($user, $ability) {
            return true;
        });
    }

    public function test_can_get_all_medication_signas()
    {
        MedicationSigna::create(['name' => '1 x sehari 1 tablet']);
        MedicationSigna::create(['name' => '3 x sehari 1 sendok teh']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/master-data/medication-signas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'legacy_id', 'name', 'is_active', 'created_at', 'updated_at'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_search_medication_signas()
    {
        MedicationSigna::create(['name' => '1 x sehari 1 tablet']);
        MedicationSigna::create(['name' => '3 x sehari 1 sendok makan']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/master-data/medication-signas?search=makan');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', '3 x sehari 1 sendok makan');
    }

    public function test_can_sort_medication_signas()
    {
        MedicationSigna::create(['name' => 'A - 1 x sehari']);
        MedicationSigna::create(['name' => 'B - 2 x sehari']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/master-data/medication-signas?sort=name&direction=desc');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'B - 2 x sehari')
            ->assertJsonPath('data.1.name', 'A - 1 x sehari');
    }

    public function test_can_create_medication_signa()
    {
        Sanctum::actingAs(User::factory()->create());

        $data = [
            'name' => '2 x sehari 1 tetes',
        ];

        $response = $this->postJson('/api/v1/master-data/medication-signas', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', '2 x sehari 1 tetes');

        $this->assertDatabaseHas('medication_signas', ['name' => '2 x sehari 1 tetes']);
    }

    public function test_cannot_create_medication_signa_without_name()
    {
        Sanctum::actingAs(User::factory()->create());

        $data = [];

        $response = $this->postJson('/api/v1/master-data/medication-signas', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_medication_signa()
    {
        $signa = MedicationSigna::create(['name' => 'Signa Lama']);
        Sanctum::actingAs(User::factory()->create());

        $data = [
            'name' => 'Signa Diupdate',
        ];

        $response = $this->putJson("/api/v1/master-data/medication-signas/{$signa->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Signa Diupdate');

        $this->assertDatabaseHas('medication_signas', ['id' => $signa->id, 'name' => 'Signa Diupdate']);
    }

    public function test_can_delete_medication_signa()
    {
        $signa = MedicationSigna::create(['name' => 'Signa Hapus']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->deleteJson("/api/v1/master-data/medication-signas/{$signa->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('medication_signas', ['id' => $signa->id]);
    }
}
