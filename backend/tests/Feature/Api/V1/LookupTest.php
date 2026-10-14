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

    public function test_generic_medicines_lookup_requires_authentication()
    {
        $this->getJson('/api/v1/lookups/generic-medicines')->assertUnauthorized();
    }

    public function test_generic_medicines_lookup_returns_options_with_filters_and_search()
    {
        $user = User::factory()->create();
        \App\Models\MasterData\GenericMedicine::create(['name' => 'Amoxicillin 500mg', 'is_active' => true]);
        $gen2 = \App\Models\MasterData\GenericMedicine::create(['name' => 'Paracetamol 500mg', 'is_active' => true]);
        $gen3 = \App\Models\MasterData\GenericMedicine::create(['name' => 'Paracetamol 250mg', 'is_active' => false]);

        // Default active only
        $response = $this->actingAs($user)->getJson('/api/v1/lookups/generic-medicines');
        $response->assertOk();
        $this->assertCount(2, $response->json());

        // Search
        $response = $this->actingAs($user)->getJson('/api/v1/lookups/generic-medicines?search=Paracetamol');
        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertEquals('Paracetamol 500mg', $response->json('0.name'));

        // All including inactive
        $response = $this->actingAs($user)->getJson('/api/v1/lookups/generic-medicines?is_active=0');
        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertEquals('Paracetamol 250mg', $response->json('0.name'));

        // With ids included
        $response = $this->actingAs($user)->getJson('/api/v1/lookups/generic-medicines?search=Amoxicillin&ids='.$gen3->id);
        $response->assertOk();
        $names = collect($response->json())->pluck('name')->toArray();
        $this->assertContains('Amoxicillin 500mg', $names);
        $this->assertContains('Paracetamol 250mg', $names);
    }
}
