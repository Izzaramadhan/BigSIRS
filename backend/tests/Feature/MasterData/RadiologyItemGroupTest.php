<?php

namespace Tests\Feature\MasterData;

use App\Models\MasterData\RadiologyItemGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RadiologyItemGroupTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_can_list_radiology_item_groups()
    {
        RadiologyItemGroup::factory()->count(15)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/radiology-item-groups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'is_active', 'created_at', 'updated_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonCount(10, 'data'); // default per_page is 10
    }

    public function test_can_search_radiology_item_groups()
    {
        RadiologyItemGroup::factory()->create(['name' => 'Kelompok A']);
        RadiologyItemGroup::factory()->create(['name' => 'Kelompok B']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/radiology-item-groups?search=Kelompok A');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Kelompok A');
    }

    public function test_can_create_radiology_item_group()
    {
        $payload = [
            'name' => 'New Group',
            'description' => 'A new test group',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/radiology-item-groups', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Group');

        $this->assertDatabaseHas('radiology_item_groups', [
            'name' => 'New Group',
        ]);
    }

    public function test_can_show_radiology_item_group()
    {
        $group = RadiologyItemGroup::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/radiology-item-groups/{$group->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $group->id)
            ->assertJsonPath('data.name', $group->name);
    }

    public function test_can_update_radiology_item_group()
    {
        $group = RadiologyItemGroup::factory()->create([
            'name' => 'Old Name',
        ]);

        $payload = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/radiology-item-groups/{$group->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('radiology_item_groups', [
            'id' => $group->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_radiology_item_group()
    {
        $group = RadiologyItemGroup::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/radiology-item-groups/{$group->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('radiology_item_groups', [
            'id' => $group->id,
        ]);
    }

    public function test_can_update_status()
    {
        $group = RadiologyItemGroup::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)->patchJson("/api/v1/master-data/radiology-item-groups/{$group->id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('radiology_item_groups', [
            'id' => $group->id,
            'is_active' => false,
        ]);
    }
}
