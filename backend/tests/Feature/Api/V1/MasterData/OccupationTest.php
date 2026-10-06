<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Employee;
use App\Models\Occupation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OccupationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/v1/master-data/occupations');
        $response->assertUnauthorized();
    }

    public function test_can_list_occupations_with_pagination_and_search()
    {
        Occupation::create(['name' => 'Petani']);
        Occupation::create(['name' => 'Pensiunan']);
        Occupation::create(['name' => 'Wiraswasta']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/occupations?search=Pe');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Petani')
            ->assertJsonPath('data.1.name', 'Pensiunan');
    }

    public function test_can_create_occupation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/occupations', [
                'name' => '  Pegawai Negeri Sipil  ',
                'is_active' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Pegawai Negeri Sipil')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('occupations', [
            'name' => 'Pegawai Negeri Sipil',
            'is_active' => 1,
        ]);
    }

    public function test_rejects_duplicate_name_on_create()
    {
        Occupation::create(['name' => 'Wiraswasta']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/occupations', [
                'name' => 'wiraswasta',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_occupation()
    {
        $occ = Occupation::create(['name' => 'Petani']);

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/master-data/occupations/'.$occ->id, [
                'name' => 'Pekebun',
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Pekebun')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_allows_update_with_same_name()
    {
        $occ = Occupation::create(['name' => 'Petani']);

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/master-data/occupations/'.$occ->id, [
                'name' => 'Petani',
            ]);

        $response->assertOk();
    }

    public function test_can_delete_occupation_if_not_used()
    {
        $occ = Occupation::create(['name' => 'Petani']);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/master-data/occupations/'.$occ->id);

        $response->assertNoContent();
        $this->assertSoftDeleted('occupations', ['id' => $occ->id]);
    }

    public function test_cannot_delete_occupation_if_used()
    {
        $occ = Occupation::create(['name' => 'Petani']);
        $employee = Employee::factory()->create(['occupation_id' => $occ->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/master-data/occupations/'.$occ->id);

        $response->assertStatus(409);
        $this->assertDatabaseHas('occupations', ['id' => $occ->id, 'deleted_at' => null]);
    }
}
