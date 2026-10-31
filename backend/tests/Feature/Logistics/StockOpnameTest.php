<?php

namespace Tests\Feature\Logistics;

use App\Models\Logistics\StockMovement;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineUnit;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Warehouse $warehouse1;

    protected Warehouse $warehouse2;

    protected Medicine $medicine1;

    protected Medicine $medicine2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Petugas Farmasi',
            'username' => 'petugas_farmasi',
            'email' => 'farmasi@example.com',
        ]);

        $unit = MedicineUnit::create([
            'code' => 'TAB',
            'name' => 'Tablet',
        ]);

        $this->warehouse1 = Warehouse::create([
            'code' => 'GDF',
            'name' => 'Gudang Utama',
            'description' => 'Gudang Pusat Logistik',
        ]);

        $this->warehouse2 = Warehouse::create([
            'code' => 'AP-RJ',
            'name' => 'Apotek Rawat Jalan',
            'description' => 'Apotek Rajal',
        ]);

        $this->medicine1 = Medicine::create([
            'code' => 'OBT001',
            'name' => 'Alprazolam 0.5mg',
            'medicine_unit_id' => $unit->id,
            'selling_price_per_unit' => 1000,
            'is_active' => true,
        ]);

        $this->medicine2 = Medicine::create([
            'code' => 'OBT002',
            'name' => 'Meloxicam 7.5mg',
            'medicine_unit_id' => $unit->id,
            'selling_price_per_unit' => 250,
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_stock_opname_list(): void
    {
        $response = $this->getJson('/api/v1/logistics/stock-opnames');
        $response->assertStatus(401);
    }

    public function test_list_fetches_real_warehouse_stocks_automatically_without_session(): void
    {
        WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine1->id,
            'quantity' => 500,
            'selling_price' => 1000,
            'batch_number' => 'BATCH-ALP-01',
            'expired_date' => '2027-10-20',
        ]);

        WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine2->id,
            'quantity' => 150,
            'selling_price' => 250,
            'batch_number' => 'BATCH-MLX-01',
            'expired_date' => '2026-05-15',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/logistics/stock-opnames');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'warehouse' => ['id', 'code', 'name'],
                        'medicine' => ['id', 'code', 'name'],
                        'unit' => ['id', 'name'],
                        'batch_number',
                        'expiration_date',
                        'selling_price',
                        'stock',
                    ],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);

        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_filter_by_warehouse_works(): void
    {
        WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine1->id,
            'quantity' => 100,
            'selling_price' => 1000,
            'batch_number' => 'WH1-BATCH',
        ]);

        WarehouseStock::create([
            'warehouse_id' => $this->warehouse2->id,
            'medicine_id' => $this->medicine2->id,
            'quantity' => 50,
            'selling_price' => 250,
            'batch_number' => 'WH2-BATCH',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/logistics/stock-opnames?warehouse_id='.$this->warehouse1->id);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->warehouse1->id, $response->json('data.0.warehouse.id'));
    }

    public function test_search_by_medicine_name_code_and_batch_works(): void
    {
        WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine1->id,
            'quantity' => 100,
            'selling_price' => 1000,
            'batch_number' => 'LOT-ALPHA-99',
        ]);

        WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine2->id,
            'quantity' => 80,
            'selling_price' => 250,
            'batch_number' => 'LOT-BETA-88',
        ]);

        // Search by medicine name
        $res1 = $this->actingAs($this->user)->getJson('/api/v1/logistics/stock-opnames?search=Alprazolam');
        $res1->assertStatus(200);
        $this->assertCount(1, $res1->json('data'));
        $this->assertEquals('Alprazolam 0.5mg', $res1->json('data.0.medicine.name'));

        // Search by medicine code
        $res2 = $this->actingAs($this->user)->getJson('/api/v1/logistics/stock-opnames?search=OBT002');
        $res2->assertStatus(200);
        $this->assertCount(1, $res2->json('data'));
        $this->assertEquals('Meloxicam 7.5mg', $res2->json('data.0.medicine.name'));

        // Search by batch number
        $res3 = $this->actingAs($this->user)->getJson('/api/v1/logistics/stock-opnames?search=ALPHA-99');
        $res3->assertStatus(200);
        $this->assertCount(1, $res3->json('data'));
        $this->assertEquals('LOT-ALPHA-99', $res3->json('data.0.batch_number'));
    }

    public function test_hide_and_show_zero_stock_works(): void
    {
        WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine1->id,
            'quantity' => 100,
            'selling_price' => 1000,
            'batch_number' => 'STOCK-ACTIVE',
        ]);

        WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine2->id,
            'quantity' => 0,
            'selling_price' => 250,
            'batch_number' => 'STOCK-EMPTY',
        ]);

        // Default: hide zero stock
        $resDefault = $this->actingAs($this->user)->getJson('/api/v1/logistics/stock-opnames');
        $resDefault->assertStatus(200);
        $this->assertCount(1, $resDefault->json('data'));
        $this->assertEquals('STOCK-ACTIVE', $resDefault->json('data.0.batch_number'));

        // Explicit: hide_zero_stock=1
        $resHide = $this->actingAs($this->user)->getJson('/api/v1/logistics/stock-opnames?hide_zero_stock=1');
        $resHide->assertStatus(200);
        $this->assertCount(1, $resHide->json('data'));

        // Explicit: hide_zero_stock=0 (tampilkan stok 0)
        $resShow = $this->actingAs($this->user)->getJson('/api/v1/logistics/stock-opnames?hide_zero_stock=0');
        $resShow->assertStatus(200);
        $this->assertCount(2, $resShow->json('data'));
    }

    public function test_pagination_and_per_page_controls_work(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            WarehouseStock::create([
                'warehouse_id' => $this->warehouse1->id,
                'medicine_id' => $this->medicine1->id,
                'quantity' => 10 + $i,
                'selling_price' => 1000,
                'batch_number' => 'BATCH-'.$i,
            ]);
        }

        // 10 data per page (Page 1)
        $res10 = $this->actingAs($this->user)->getJson('/api/v1/logistics/stock-opnames?per_page=10&page=1');
        $res10->assertStatus(200);
        $this->assertCount(10, $res10->json('data'));
        $this->assertEquals(15, $res10->json('meta.total'));
        $this->assertEquals(2, $res10->json('meta.last_page'));

        // Page 2
        $resPage2 = $this->actingAs($this->user)->getJson('/api/v1/logistics/stock-opnames?per_page=10&page=2');
        $resPage2->assertStatus(200);
        $this->assertCount(5, $resPage2->json('data'));

        // 25 data per page
        $res25 = $this->actingAs($this->user)->getJson('/api/v1/logistics/stock-opnames?per_page=25');
        $res25->assertStatus(200);
        $this->assertCount(15, $res25->json('data'));
    }

    public function test_can_edit_expiration_date_selling_price_and_increase_stock(): void
    {
        $stock = WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine1->id,
            'quantity' => 100,
            'selling_price' => 1000,
            'batch_number' => 'LOT-01',
            'expired_date' => '2026-01-01',
        ]);

        $payload = [
            'stock' => 150,
            'selling_price' => 1200,
            'expiration_date' => '2028-06-30',
        ];

        $response = $this->actingAs($this->user)->patchJson("/api/v1/logistics/stock-opnames/{$stock->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.stock', 150)
            ->assertJsonPath('data.selling_price', 1200)
            ->assertJsonPath('data.expiration_date', '2028-06-30')
            ->assertJsonPath('message', 'Stok opname berhasil diperbarui.');

        $stock->refresh();
        $this->assertEquals(150, (float) $stock->quantity);
        $this->assertEquals(1200, (float) $stock->selling_price);
        $this->assertEquals('2028-06-30', $stock->expired_date->format('Y-m-d'));

        // Verify stock movement ledger
        $movement = StockMovement::where('warehouse_stock_id', $stock->id)->latest()->first();
        $this->assertNotNull($movement);
        $this->assertEquals('stock_opname_adjustment', $movement->movement_type);
        $this->assertEquals('in', $movement->direction);
        $this->assertEquals(50, (float) $movement->quantity);
        $this->assertEquals(100, (float) $movement->balance_before);
        $this->assertEquals(150, (float) $movement->balance_after);
        $this->assertEquals($this->user->id, $movement->created_by);
    }

    public function test_can_decrease_stock_and_records_audit_ledger_accurately(): void
    {
        $stock = WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine1->id,
            'quantity' => 500,
            'selling_price' => 1000,
            'batch_number' => 'LOT-02',
            'expired_date' => '2027-10-20',
        ]);

        $payload = [
            'stock' => 450,
            'selling_price' => 1000,
            'expiration_date' => '2027-10-20',
        ];

        $response = $this->actingAs($this->user)->patchJson("/api/v1/logistics/stock-opnames/{$stock->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.stock', 450);

        $stock->refresh();
        $this->assertEquals(450, (float) $stock->quantity);

        $movement = StockMovement::where('warehouse_stock_id', $stock->id)->latest()->first();
        $this->assertNotNull($movement);
        $this->assertEquals('stock_opname_adjustment', $movement->movement_type);
        $this->assertEquals('out', $movement->direction);
        $this->assertEquals(50, (float) $movement->quantity);
        $this->assertEquals(500, (float) $movement->balance_before);
        $this->assertEquals(450, (float) $movement->balance_after);
    }

    public function test_can_adjust_stock_to_zero(): void
    {
        $stock = WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine1->id,
            'quantity' => 25,
            'selling_price' => 500,
            'batch_number' => 'LOT-ZERO',
        ]);

        $payload = [
            'stock' => 0,
            'selling_price' => 500,
        ];

        $response = $this->actingAs($this->user)->patchJson("/api/v1/logistics/stock-opnames/{$stock->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.stock', 0);

        $stock->refresh();
        $this->assertEquals(0, (float) $stock->quantity);
    }

    public function test_negative_stock_is_rejected(): void
    {
        $stock = WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine1->id,
            'quantity' => 100,
            'selling_price' => 1000,
        ]);

        $payload = [
            'stock' => -10,
            'selling_price' => 1000,
        ];

        $response = $this->actingAs($this->user)->patchJson("/api/v1/logistics/stock-opnames/{$stock->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stock']);

        $stock->refresh();
        $this->assertEquals(100, (float) $stock->quantity);
        $this->assertEquals(0, StockMovement::count());
    }

    public function test_warehouse_cannot_be_changed_via_stock_opname_edit(): void
    {
        $stock = WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine1->id,
            'quantity' => 100,
            'selling_price' => 1000,
        ]);

        $payload = [
            'warehouse_id' => $this->warehouse2->id,
            'stock' => 100,
            'selling_price' => 1000,
        ];

        $response = $this->actingAs($this->user)->patchJson("/api/v1/logistics/stock-opnames/{$stock->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['warehouse_id']);

        $stock->refresh();
        $this->assertEquals($this->warehouse1->id, $stock->warehouse_id);
    }

    public function test_atomic_transaction_rolls_back_if_error_occurs(): void
    {
        $this->withoutExceptionHandling();

        $stock = WarehouseStock::create([
            'warehouse_id' => $this->warehouse1->id,
            'medicine_id' => $this->medicine1->id,
            'quantity' => 200,
            'selling_price' => 1000,
        ]);

        // Mock DB failure during movement creation
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \RuntimeException('Database failure simulation'));

        $payload = [
            'stock' => 250,
            'selling_price' => 1000,
        ];

        $this->expectException(\RuntimeException::class);
        $this->actingAs($this->user)->patchJson("/api/v1/logistics/stock-opnames/{$stock->id}", $payload);

        $stock->refresh();
        $this->assertEquals(200, (float) $stock->quantity);
    }
}
