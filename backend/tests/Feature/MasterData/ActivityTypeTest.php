<?php

namespace Tests\Feature\MasterData;

use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTypeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected string $endpoint = '/api/v1/master-data/activity-types';

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_can_list_activity_types_with_pagination()
    {
        ActivityType::factory()->count(15)->create();

        $response = $this->actingAs($this->admin)->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'parent_id', 'is_active', 'created_at', 'updated_at'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('meta.total', 15);
    }

    public function test_can_search_activity_types()
    {
        ActivityType::factory()->create(['name' => 'Diet Jantung']);
        ActivityType::factory()->create(['name' => 'Diet Rendah Lemak']);

        $response = $this->actingAs($this->admin)->getJson($this->endpoint.'?search=jantung');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Diet Jantung');
    }

    public function test_can_filter_by_status()
    {
        ActivityType::factory()->create(['is_active' => true]);
        ActivityType::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)->getJson($this->endpoint.'?is_active=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_active', true);
    }

    public function test_can_create_activity_type()
    {
        $data = [
            'name' => 'Diet Diabetes',
            'parent_id' => null,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->postJson($this->endpoint, $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Diet Diabetes');

        $this->assertDatabaseHas('activity_types', [
            'name' => 'Diet Diabetes',
            'parent_id' => null,
        ]);
    }

    public function test_cannot_create_duplicate_activity_type()
    {
        ActivityType::factory()->create(['name' => 'Diet Hipertensi']);

        $response = $this->actingAs($this->admin)->postJson($this->endpoint, [
            'name' => 'Diet Hipertensi',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_activity_type()
    {
        $activityType = ActivityType::factory()->create();

        $response = $this->actingAs($this->admin)->putJson("{$this->endpoint}/{$activityType->id}", [
            'name' => 'Diet Updated',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Diet Updated');

        $this->assertDatabaseHas('activity_types', [
            'id' => $activityType->id,
            'name' => 'Diet Updated',
        ]);
    }

    public function test_can_update_status()
    {
        $activityType = ActivityType::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->patchJson("{$this->endpoint}/{$activityType->id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('activity_types', [
            'id' => $activityType->id,
            'is_active' => false,
        ]);
    }

    public function test_can_soft_delete_activity_type()
    {
        $activityType = ActivityType::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("{$this->endpoint}/{$activityType->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('activity_types', [
            'id' => $activityType->id,
        ]);
    }

    public function test_cannot_delete_parent_with_children()
    {
        $parent = ActivityType::factory()->create();
        ActivityType::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin)->deleteJson("{$this->endpoint}/{$parent->id}");
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id']);

        $this->assertDatabaseHas('activity_types', [
            'id' => $parent->id,
            'deleted_at' => null,
        ]);
    }

    public function test_cannot_set_self_as_parent()
    {
        $activityType = ActivityType::factory()->create();

        $response = $this->actingAs($this->admin)->putJson("{$this->endpoint}/{$activityType->id}", [
            'name' => 'Updated Name',
            'parent_id' => $activityType->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_cannot_set_descendant_as_parent()
    {
        $parent = ActivityType::factory()->create();
        $child = ActivityType::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin)->putJson("{$this->endpoint}/{$parent->id}", [
            'name' => 'Updated Name',
            'parent_id' => $child->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }
}
