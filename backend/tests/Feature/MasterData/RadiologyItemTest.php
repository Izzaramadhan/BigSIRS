<?php

namespace Tests\Feature\MasterData;

use App\Models\MasterData\RadiologyItem;
use App\Models\MasterData\RadiologyItemGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RadiologyItemTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_radiology_items()
    {
        RadiologyItem::factory()->count(15)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/radiology-items');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'radiology_item_group_id',
                             'is_active',
                             'group'
                         ]
                     ],
                     'links',
                     'meta'
                 ]);
    }

    public function test_can_search_radiology_items_by_name()
    {
        RadiologyItem::factory()->create(['name' => 'Specific Item Name']);
        RadiologyItem::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
                         ->getJson('/api/v1/master-data/radiology-items?search=Specific Item');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Specific Item Name', $response->json('data.0.name'));
    }

    public function test_can_filter_radiology_items_by_group()
    {
        $group1 = RadiologyItemGroup::factory()->create();
        $group2 = RadiologyItemGroup::factory()->create();

        RadiologyItem::factory()->count(3)->create(['radiology_item_group_id' => $group1->id]);
        RadiologyItem::factory()->count(2)->create(['radiology_item_group_id' => $group2->id]);

        $response = $this->actingAs($this->user)
                         ->getJson('/api/v1/master-data/radiology-items?radiology_item_group_id=' . $group1->id);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_create_radiology_item()
    {
        $group = RadiologyItemGroup::factory()->create();

        $payload = [
            'name' => 'New Radiology Item',
            'radiology_item_group_id' => $group->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/radiology-items', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'New Radiology Item');

        $this->assertDatabaseHas('radiology_items', [
            'name' => 'New Radiology Item',
            'radiology_item_group_id' => $group->id,
        ]);
    }

    public function test_can_show_radiology_item()
    {
        $item = RadiologyItem::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/v1/master-data/radiology-items/{$item->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $item->id)
                 ->assertJsonPath('data.name', $item->name);
    }

    public function test_can_update_radiology_item()
    {
        $item = RadiologyItem::factory()->create();
        $newGroup = RadiologyItemGroup::factory()->create();

        $payload = [
            'name' => 'Updated Radiology Item Name',
            'radiology_item_group_id' => $newGroup->id,
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/radiology-items/{$item->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Updated Radiology Item Name')
                 ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('radiology_items', [
            'id' => $item->id,
            'name' => 'Updated Radiology Item Name',
            'radiology_item_group_id' => $newGroup->id,
            'is_active' => false,
        ]);
    }

    public function test_can_delete_radiology_item()
    {
        $item = RadiologyItem::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/radiology-items/{$item->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('radiology_items', [
            'id' => $item->id,
        ]);
    }

    public function test_can_update_radiology_item_status()
    {
        $item = RadiologyItem::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)->patchJson("/api/v1/master-data/radiology-items/{$item->id}/status", [
            'is_active' => false
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('radiology_items', [
            'id' => $item->id,
            'is_active' => false,
        ]);
    }
}
