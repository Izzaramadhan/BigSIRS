<?php

namespace Tests\Feature\MasterData;

use App\Models\DietType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DietTypeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected string $endpoint = '/api/v1/master-data/diet-types';

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_can_list_diet_types_with_pagination()
    {
        DietType::factory()->count(15)->create();

        $response = $this->actingAs($this->admin)->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'created_at', 'updated_at'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('meta.total', 15);
    }

    public function test_can_search_diet_types()
    {
        DietType::factory()->create(['name' => 'Diet Jantung', 'description' => 'Untuk pasien jantung']);
        DietType::factory()->create(['name' => 'Diet Rendah Lemak']);

        $response = $this->actingAs($this->admin)->getJson($this->endpoint.'?search=jantung');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Diet Jantung');
    }



    public function test_can_create_diet_type()
    {
        $data = [
            'name' => 'Diet Diabetes',
            'description' => 'Diet untuk pasien diabetes',
        ];

        $response = $this->actingAs($this->admin)->postJson($this->endpoint, $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Diet Diabetes');

        $this->assertDatabaseHas('diet_types', [
            'name' => 'Diet Diabetes',
            'description' => 'Diet untuk pasien diabetes',
        ]);
    }

    public function test_cannot_create_duplicate_diet_type()
    {
        DietType::factory()->create(['name' => 'Diet Hipertensi']);

        $response = $this->actingAs($this->admin)->postJson($this->endpoint, [
            'name' => 'Diet Hipertensi',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_diet_type()
    {
        $dietType = DietType::factory()->create();

        $response = $this->actingAs($this->admin)->putJson("{$this->endpoint}/{$dietType->id}", [
            'name' => 'Diet Updated',
            'description' => 'Updated Description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Diet Updated');

        $this->assertDatabaseHas('diet_types', [
            'id' => $dietType->id,
            'name' => 'Diet Updated',
            'description' => 'Updated Description',
        ]);
    }



    public function test_can_soft_delete_diet_type()
    {
        $dietType = DietType::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("{$this->endpoint}/{$dietType->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('diet_types', [
            'id' => $dietType->id,
        ]);
    }
}
