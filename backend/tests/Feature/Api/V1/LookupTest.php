<?php

namespace Tests\Feature\Api\V1;

use App\Models\MasterData\Specialization;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        LegacyAccess::clearCache();

        DB::connection('legacy')->statement('
            CREATE TABLE IF NOT EXISTS ref_generik (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_hlp INTEGER NULL,
                nama_objek VARCHAR(255) NOT NULL,
                sediaan VARCHAR(255) NULL,
                status INTEGER DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                id_user INTEGER NULL
            )
        ');
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

        DB::connection('legacy')->table('ref_generik')->insert([
            ['id' => 1, 'nama_objek' => 'Amoxicillin 500mg', 'status' => 1, 'created_at' => now()],
            ['id' => 2, 'nama_objek' => 'Paracetamol 500mg', 'status' => 1, 'created_at' => now()],
            ['id' => 3, 'nama_objek' => 'Paracetamol 250mg', 'status' => 0, 'created_at' => now()],
        ]);

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
        $response = $this->actingAs($user)->getJson('/api/v1/lookups/generic-medicines?search=Amoxicillin&ids=3');
        $response->assertOk();
        $names = collect($response->json())->pluck('name')->toArray();
        $this->assertContains('Amoxicillin 500mg', $names);
        $this->assertContains('Paracetamol 250mg', $names);
    }
}
