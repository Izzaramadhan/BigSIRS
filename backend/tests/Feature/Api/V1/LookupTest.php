<?php

namespace Tests\Feature\Api\V1;

use App\Models\MasterData\Specialization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_specializations_lookup_requires_authentication()
    {
        $this->getJson('/api/v1/lookups/specializations')->assertUnauthorized();
    }

    public function test_specializations_lookup_returns_options()
    {
        $user = User::factory()->create();
        Specialization::factory()->create(['name' => 'Spec A', 'is_active' => true]);
        Specialization::factory()->create(['name' => 'Spec B', 'is_active' => true]);
        Specialization::factory()->create(['name' => 'Spec C', 'is_active' => false]);

        $response = $this->actingAs($user)->getJson('/api/v1/lookups/specializations');
        $response->assertOk();
        $this->assertCount(3, $response->json());
        
        $response = $this->actingAs($user)->getJson('/api/v1/lookups/specializations?is_active=1');
        $response->assertOk();
        $this->assertCount(2, $response->json());
    }
}
