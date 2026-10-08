<?php

namespace Tests\Feature\MasterData;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\MasterData\RadiologyGroup;
use App\Models\MasterData\RadiologyCategory;
use App\Models\MasterData\RadiologyType;
use App\Models\ActivityType;
use App\Models\MasterData\RadiologyItemGroup;
use App\Models\User;

class RadiologyGroupTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_radiology_groups(): void
    {
        RadiologyGroup::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/radiology-groups');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_search_radiology_groups(): void
    {
        RadiologyGroup::factory()->create(['name' => 'General X-Ray']);
        RadiologyGroup::factory()->create(['name' => 'Dental']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/master-data/radiology-groups?search=General');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'General X-Ray');
    }

    public function test_can_create_radiology_group(): void
    {
        $category = RadiologyCategory::factory()->create();
        $type = RadiologyType::factory()->create();
        $activity = ActivityType::factory()->create();
        $itemGroup = RadiologyItemGroup::factory()->create();

        $data = [
            'name' => 'New Group',
            'radiology_category_id' => $category->id,
            'radiology_type_id' => $type->id,
            'activity_type_id' => $activity->id,
            'price' => 50000,
            'interpretation_price' => 15000,
            'radiology_item_group_ids' => [$itemGroup->id],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/master-data/radiology-groups', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Group');

        $this->assertDatabaseHas('radiology_groups', [
            'name' => 'New Group',
            'price' => 50000,
        ]);

        $this->assertDatabaseHas('radiology_group_item_groups', [
            'radiology_item_group_id' => $itemGroup->id,
        ]);
    }

    public function test_can_update_radiology_group(): void
    {
        $group = RadiologyGroup::factory()->create();
        $itemGroup = RadiologyItemGroup::factory()->create();

        $data = [
            'name' => 'Updated Group',
            'radiology_category_id' => $group->radiology_category_id,
            'radiology_type_id' => $group->radiology_type_id,
            'activity_type_id' => $group->activity_type_id,
            'price' => 60000,
            'interpretation_price' => 20000,
            'radiology_item_group_ids' => [$itemGroup->id],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/master-data/radiology-groups/{$group->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Group');

        $this->assertDatabaseHas('radiology_groups', [
            'id' => $group->id,
            'name' => 'Updated Group',
        ]);
        
        $this->assertDatabaseHas('radiology_group_item_groups', [
            'radiology_group_id' => $group->id,
            'radiology_item_group_id' => $itemGroup->id,
        ]);
    }

    public function test_can_delete_radiology_group(): void
    {
        $group = RadiologyGroup::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/master-data/radiology-groups/{$group->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('radiology_groups', ['id' => $group->id]);
    }

    public function test_can_update_status(): void
    {
        $group = RadiologyGroup::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)->patchJson("/api/v1/master-data/radiology-groups/{$group->id}/status", [
            'is_active' => false
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('radiology_groups', [
            'id' => $group->id,
            'is_active' => false
        ]);
    }
}
