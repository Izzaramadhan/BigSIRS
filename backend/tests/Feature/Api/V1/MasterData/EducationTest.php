<?php

namespace Tests\Feature\Api\V1\MasterData;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Education;

class EducationTest extends TestCase
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
        $response = $this->getJson('/api/v1/master-data/educations');
        $response->assertUnauthorized();
    }

    public function test_can_list_educations_with_pagination_and_search()
    {
        Education::create(['name' => 'SD']);
        Education::create(['name' => 'SMP']);
        Education::create(['name' => 'SMA']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/educations?search=SM');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'SMP')
            ->assertJsonPath('data.1.name', 'SMA');
    }

    public function test_can_create_education()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/educations', [
                'name' => '  S1 Teknik  ',
                'is_active' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'S1 Teknik')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('educations', [
            'name' => 'S1 Teknik',
            'is_active' => 1
        ]);
    }

    public function test_rejects_duplicate_name_on_create()
    {
        Education::create(['name' => 'S1 Teknik']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/educations', [
                'name' => 's1 teknik',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_education()
    {
        $edu = Education::create(['name' => 'SD']);

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/master-data/educations/' . $edu->id, [
                'name' => 'Sekolah Dasar',
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Sekolah Dasar')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_allows_update_with_same_name()
    {
        $edu = Education::create(['name' => 'SD']);

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/master-data/educations/' . $edu->id, [
                'name' => 'SD',
            ]);

        $response->assertOk();
    }

    public function test_can_delete_education_if_not_used()
    {
        $edu = Education::create(['name' => 'SD']);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/master-data/educations/' . $edu->id);

        $response->assertNoContent();
        $this->assertSoftDeleted('educations', ['id' => $edu->id]);
    }
    
    public function test_cannot_delete_education_if_used()
    {
        $edu = Education::create(['name' => 'SD']);
        $employee = \App\Models\Employee::factory()->create(['education_id' => $edu->id]);
        
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/master-data/educations/' . $edu->id);

        $response->assertStatus(409);
        $this->assertDatabaseHas('educations', ['id' => $edu->id, 'deleted_at' => null]);
    }
}
