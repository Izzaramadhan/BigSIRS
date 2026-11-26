<?php

namespace Tests\Feature\Api\V1;

use App\Enums\GuarantorType;
use App\Models\Guarantor;
use App\Models\MasterData\Specialization;
use App\Models\User;
use App\Support\LegacyAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function test_guarantors_lookup_requires_authentication(): void
    {
        $this->getJson('/api/v1/lookups/guarantors')->assertUnauthorized();
    }

    public function test_guarantors_lookup_returns_active_and_preserved_historical_options_in_internal_and_legacy_modes(): void
    {
        $user = User::factory()->create();

        $activeUmum = Guarantor::factory()->create([
            'code' => 'UMM',
            'name' => 'UMUM',
            'type' => GuarantorType::SelfPay,
            'is_active' => true,
        ]);
        $activePrivate = Guarantor::factory()->create([
            'code' => 'PRV-1',
            'name' => 'Asuransi Mandiri Inhealth',
            'type' => GuarantorType::PrivateInsurance,
            'is_active' => true,
        ]);
        $inactiveOld = Guarantor::factory()->create([
            'code' => 'OLD-1',
            'name' => 'Asuransi Lama Nonaktif',
            'type' => GuarantorType::PrivateInsurance,
            'is_active' => false,
        ]);

        // Default internal mode: active only
        $resDefault = $this->actingAs($user)->getJson('/api/v1/lookups/guarantors');
        $resDefault->assertOk();
        $defaultIds = collect($resDefault->json())->pluck('id')->all();
        $this->assertContains($activeUmum->id, $defaultIds);
        $this->assertContains($activePrivate->id, $defaultIds);
        $this->assertNotContains($inactiveOld->id, $defaultIds);

        // Search filter
        $resSearch = $this->actingAs($user)->getJson('/api/v1/lookups/guarantors?search=Inhealth');
        $resSearch->assertOk()->assertJsonCount(1);
        $this->assertSame('self_pay', $resDefault->json(
            collect($resDefault->json())->search(fn ($i) => $i['id'] === $activeUmum->id) . '.type'
        ));

        // Preserve historical inactive guarantor via ids parameter
        $resHistorical = $this->actingAs($user)->getJson("/api/v1/lookups/guarantors?ids={$inactiveOld->id}");
        $resHistorical->assertOk();
        $histIds = collect($resHistorical->json())->pluck('id')->all();
        $this->assertContains($inactiveOld->id, $histIds);

        // Legacy ref_jenis_asuransi mode
        Schema::connection('legacy')->create('ref_jenis_asuransi', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode', 30)->nullable();
            $table->string('jenis_asuransi', 100);
            $table->string('type', 20)->default('UMUM');
            $table->integer('pemerintah')->default(0);
            $table->string('id_inacbg', 10)->nullable();
            $table->integer('status')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->integer('id_user')->nullable();
        });
        LegacyAccess::clearCache();

        DB::connection('legacy')->table('ref_jenis_asuransi')->insert([
            ['id' => 10, 'kode' => 'BPJS', 'jenis_asuransi' => 'BPJS Kesehatan', 'type' => 'BPJS', 'status' => 1, 'deleted_at' => null],
            ['id' => 11, 'kode' => 'UMM', 'jenis_asuransi' => 'Bayar Tunai RS', 'type' => 'UMUM', 'status' => 1, 'deleted_at' => null],
            ['id' => 12, 'kode' => 'ARC', 'jenis_asuransi' => 'Asuransi Arsip', 'type' => 'PRIVATE', 'status' => 0, 'deleted_at' => null],
        ]);

        $resLegacy = $this->actingAs($user)->getJson('/api/v1/lookups/guarantors?ids=12');
        $resLegacy->assertOk();
        $legacyMap = collect($resLegacy->json())->keyBy('id');
        $this->assertTrue($legacyMap->has(10));
        $this->assertTrue($legacyMap->has(11));
        $this->assertTrue($legacyMap->has(12));
        $this->assertSame('self_pay', $legacyMap->get(11)['type']);
        $this->assertFalse($legacyMap->get(12)['is_active']);
    }
}
