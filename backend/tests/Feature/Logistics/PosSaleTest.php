<?php

namespace Tests\Feature\Logistics;

use App\Models\Logistics\PosSale;
use App\Models\Logistics\PosSaleItem;
use App\Models\Logistics\StockMovement;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineCategory;
use App\Models\MasterData\MedicineUnit;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosSaleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Warehouse $warehousePusat;
    protected Warehouse $warehouseRajal;
    protected Medicine $medicineA;
    protected Medicine $medicineB;
    protected WarehouseStock $stockA;
    protected WarehouseStock $stockB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Rina Marlina (Kasir 1)',
            'email' => 'rina.marlina@simrs.test',
        ]);

        $this->warehousePusat = Warehouse::create([
            'code' => 'GDF-01',
            'name' => 'Farmasi Pusat (Kasir 1)',
            'description' => 'Kasir apotek farmasi pusat 1',
        ]);

        $this->warehouseRajal = Warehouse::create([
            'code' => 'AP-RJ',
            'name' => 'Apotek Rawat Jalan',
            'description' => 'Apotek rawat jalan',
        ]);

        $unitStrip = MedicineUnit::create(['name' => 'Strip']);
        $unitBotol = MedicineUnit::create(['name' => 'Botol']);
        $categoryOtc = MedicineCategory::create(['name' => 'Obat Bebas']);

        $this->medicineA = Medicine::create([
            'code' => 'MED-OTC-001',
            'name' => 'Paracetamol 500 mg Tablet',
            'medicine_unit_id' => $unitStrip->id,
            'medicine_category_id' => $categoryOtc->id,
            'selling_price_per_unit' => 15000,
            'is_active' => true,
        ]);

        $this->medicineB = Medicine::create([
            'code' => 'MED-OTC-002',
            'name' => 'Mylanta Cair 150 ml',
            'medicine_unit_id' => $unitBotol->id,
            'medicine_category_id' => $categoryOtc->id,
            'selling_price_per_unit' => 48000,
            'is_active' => true,
        ]);

        $this->stockA = WarehouseStock::create([
            'warehouse_id' => $this->warehousePusat->id,
            'medicine_id' => $this->medicineA->id,
            'quantity' => 100,
            'selling_price' => 15000,
            'batch_number' => 'PCT-202610',
            'expired_date' => Carbon::now()->addYear(),
        ]);

        $this->stockB = WarehouseStock::create([
            'warehouse_id' => $this->warehousePusat->id,
            'medicine_id' => $this->medicineB->id,
            'quantity' => 50,
            'selling_price' => 48000,
            'batch_number' => 'MYL-202611',
            'expired_date' => Carbon::now()->addYear(),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_pos_sales(): void
    {
        $response = $this->getJson('/api/v1/logistics/pos-sales');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_pos_sales(): void
    {
        Sanctum::actingAs($this->user);

        PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0001',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Bpk. Rahmat',
            'customer_type' => 'Umum',
            'cashier_name' => 'Rina Marlina',
            'payment_method' => 'Tunai',
            'total_quantity' => 2,
            'total_amount' => 30000,
            'paid_amount' => 50000,
            'change_amount' => 20000,
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/logistics/pos-sales');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'transactionNumber',
                        'dateDisplay',
                        'timeDisplay',
                        'customerName',
                        'totalAmount',
                        'paymentMethod',
                        'status',
                    ],
                ],
                'meta',
            ])
            ->assertJsonFragment(['transactionNumber' => 'TR-OTC-20261030-0001']);
    }

    public function test_can_filter_pos_sales_by_transaction_number(): void
    {
        Sanctum::actingAs($this->user);

        PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0001',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Bpk. Rahmat',
            'status' => 'completed',
            'total_amount' => 30000,
        ]);

        PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0002',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Ibu Siti',
            'status' => 'completed',
            'total_amount' => 48000,
        ]);

        $response = $this->getJson('/api/v1/logistics/pos-sales?transactionNumber=0002');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['transactionNumber' => 'TR-OTC-20261030-0002']);
    }

    public function test_can_filter_pos_sales_by_customer_name(): void
    {
        Sanctum::actingAs($this->user);

        PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0001',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Bpk. Bambang Pamungkas',
            'status' => 'completed',
        ]);

        PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0002',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Ibu Siti Rahma',
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/logistics/pos-sales?customerName=Bambang');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['customerName' => 'Bpk. Bambang Pamungkas']);
    }

    public function test_can_filter_pos_sales_by_date_range(): void
    {
        Sanctum::actingAs($this->user);

        PosSale::create([
            'transaction_number' => 'TR-OTC-20261001-0001',
            'transaction_date' => '2026-10-01 10:00:00',
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Cust 1',
            'status' => 'completed',
        ]);

        PosSale::create([
            'transaction_number' => 'TR-OTC-20261015-0002',
            'transaction_date' => '2026-10-15 10:00:00',
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Cust 2',
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/logistics/pos-sales?startDate=2026-10-10&endDate=2026-10-20');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['transactionNumber' => 'TR-OTC-20261015-0002']);
    }

    public function test_can_filter_pos_sales_by_status(): void
    {
        Sanctum::actingAs($this->user);

        PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0001',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'status' => 'completed',
        ]);

        PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0002',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/logistics/pos-sales?status=Draft');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['transactionNumber' => 'TR-OTC-20261030-0002', 'status' => 'Draft']);
    }

    public function test_can_get_summary_statistics(): void
    {
        Sanctum::actingAs($this->user);

        PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0001',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'total_quantity' => 5,
            'total_amount' => 75000,
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/logistics/pos-sales/summary');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'todayTransactions',
                    'todayUnit',
                    'productsSold',
                    'totalSalesValue',
                    'cancelledTransactions',
                ],
            ]);
    }

    public function test_can_get_form_options(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/logistics/pos-sales/form-options');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'locations',
                    'paymentMethods',
                    'statuses',
                    'customerTypes',
                    'otcMedicines',
                ],
            ]);
    }

    public function test_can_create_completed_pos_sale_and_deduct_stock(): void
    {
        Sanctum::actingAs($this->user);

        $initialStockA = (float) $this->stockA->fresh()->quantity;

        $payload = [
            'customerName' => 'Bpk. Handoko',
            'customerType' => 'Umum',
            'location' => 'Farmasi Pusat (Kasir 1)',
            'paymentMethod' => 'Tunai',
            'paidAmount' => 50000,
            'items' => [
                [
                    'medicine_id' => $this->medicineA->id,
                    'warehouse_stock_id' => $this->stockA->id,
                    'quantity' => 2,
                    'price' => 15000,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/pos-sales', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('data.customerName', 'Bpk. Handoko')
            ->assertJsonPath('data.status', 'Lunas')
            ->assertJsonPath('data.totalAmount', 30000)
            ->assertJsonPath('data.changeAmount', 20000);

        // Verify stock deducted
        $this->assertEquals($initialStockA - 2, (float) $this->stockA->fresh()->quantity);

        // Verify stock movement
        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $this->medicineA->id,
            'movement_type' => 'pos_sale',
            'direction' => 'OUT',
            'quantity' => 2,
        ]);
    }

    public function test_cannot_create_sale_exceeding_tracked_stock(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'customerName' => 'Bpk. Tamak',
            'location' => 'Farmasi Pusat (Kasir 1)',
            'paymentMethod' => 'Tunai',
            'items' => [
                [
                    'medicine_id' => $this->medicineA->id,
                    'warehouse_stock_id' => $this->stockA->id,
                    'quantity' => 9999, // Exceeds 100
                    'price' => 15000,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/pos-sales', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_can_create_draft_pos_sale_without_deducting_stock(): void
    {
        Sanctum::actingAs($this->user);

        $initialStockA = (float) $this->stockA->fresh()->quantity;

        $payload = [
            'isDraft' => true,
            'customerName' => 'Draft Customer',
            'location' => 'Farmasi Pusat (Kasir 1)',
            'items' => [
                [
                    'medicine_id' => $this->medicineA->id,
                    'quantity' => 3,
                    'price' => 15000,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/pos-sales', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'Draft');

        // Verify stock NOT deducted
        $this->assertEquals($initialStockA, (float) $this->stockA->fresh()->quantity);
    }

    public function test_can_update_draft_pos_sale(): void
    {
        Sanctum::actingAs($this->user);

        $sale = PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0010',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Initial Draft',
            'status' => 'draft',
            'total_amount' => 15000,
        ]);

        $sale->items()->create([
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'quantity' => 1,
            'price' => 15000,
            'subtotal' => 15000,
        ]);

        $response = $this->putJson("/api/v1/logistics/pos-sales/{$sale->id}", [
            'isDraft' => true,
            'customerName' => 'Updated Draft Name',
            'items' => [
                [
                    'medicine_id' => $this->medicineB->id,
                    'quantity' => 1,
                    'price' => 48000,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.customerName', 'Updated Draft Name')
            ->assertJsonPath('data.totalAmount', 48000);
    }

    public function test_can_delete_draft_pos_sale(): void
    {
        Sanctum::actingAs($this->user);

        $sale = PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0011',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Draft To Delete',
            'status' => 'draft',
        ]);

        $response = $this->deleteJson("/api/v1/logistics/pos-sales/{$sale->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('pos_sales', ['id' => $sale->id]);
    }

    public function test_cannot_delete_completed_pos_sale(): void
    {
        Sanctum::actingAs($this->user);

        $sale = PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0012',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Completed Sale',
            'status' => 'completed',
        ]);

        $response = $this->deleteJson("/api/v1/logistics/pos-sales/{$sale->id}");
        $response->assertStatus(403);
    }

    public function test_can_cancel_completed_pos_sale_and_restore_stock(): void
    {
        Sanctum::actingAs($this->user);

        // First create completed sale
        $createRes = $this->postJson('/api/v1/logistics/pos-sales', [
            'customerName' => 'Pembeli Void',
            'location' => 'Farmasi Pusat (Kasir 1)',
            'paymentMethod' => 'Tunai',
            'items' => [
                [
                    'medicine_id' => $this->medicineA->id,
                    'warehouse_stock_id' => $this->stockA->id,
                    'quantity' => 5,
                    'price' => 15000,
                ],
            ],
        ]);

        $createRes->assertStatus(201);
        $saleId = $createRes->json('data.id');

        $stockAfterSale = (float) $this->stockA->fresh()->quantity;
        $this->assertEquals(95, $stockAfterSale);

        // Now cancel (void)
        $cancelRes = $this->postJson("/api/v1/logistics/pos-sales/{$saleId}/cancel", [
            'reason' => 'Salah input oleh kasir, barang dikembalikan',
        ]);

        $cancelRes->assertStatus(200)
            ->assertJsonPath('data.status', 'Dibatalkan')
            ->assertJsonPath('data.voidReason', 'Salah input oleh kasir, barang dikembalikan');

        // Stock restored back to 100
        $this->assertEquals(100, (float) $this->stockA->fresh()->quantity);

        // Stock movement IN recorded
        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $this->medicineA->id,
            'movement_type' => 'pos_sale_cancellation',
            'direction' => 'IN',
            'quantity' => 5,
        ]);
    }

    public function test_cannot_cancel_already_cancelled_sale(): void
    {
        Sanctum::actingAs($this->user);

        $sale = PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0013',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Already cancelled',
        ]);

        $response = $this->postJson("/api/v1/logistics/pos-sales/{$sale->id}/cancel", [
            'reason' => 'Try cancel again',
        ]);

        $response->assertStatus(403);
    }

    public function test_can_export_pos_sales_csv(): void
    {
        Sanctum::actingAs($this->user);

        PosSale::create([
            'transaction_number' => 'TR-OTC-20261030-0020',
            'transaction_date' => Carbon::now(),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Export Tester',
            'status' => 'completed',
        ]);

        $response = $this->get('/api/v1/logistics/pos-sales/export');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_can_search_customers(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/logistics/pos-sales/customers');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'name', 'code'],
                ],
            ]);
    }

    public function test_can_create_pos_sale_header_with_tanggal_and_pembeli(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/logistics/pos-sales', [
            'transaction_date' => '2026-09-02',
            'customer_name' => 'Person 45655962',
            'isDraft' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customerName', 'Person 45655962')
            ->assertJsonPath('data.formattedDate', '02-09-2026')
            ->assertJsonPath('data.pembeli', 'Person 45655962');
    }

    public function test_can_add_item_to_pos_sale_detail_and_deduct_stock(): void
    {
        Sanctum::actingAs($this->user);

        $sale = PosSale::create([
            'transaction_number' => 'TR-OTC-20260902-0001',
            'transaction_date' => Carbon::parse('2026-09-02'),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Person 45655962',
            'status' => 'draft',
        ]);

        $initialStock = (float) $this->stockA->fresh()->quantity;

        $response = $this->postJson("/api/v1/logistics/pos-sales/{$sale->id}/items", [
            'medicine_id' => $this->medicineA->id,
            'warehouse_id' => $this->warehousePusat->id,
            'warehouse_stock_id' => $this->stockA->id,
            'quantity' => 5,
            'price' => 6000,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.medicineName', $this->medicineA->name)
            ->assertJsonPath('data.quantity', 5)
            ->assertJsonPath('data.price', 6000)
            ->assertJsonPath('data.subtotal', 30000);

        // Verify stock deducted
        $this->assertEquals($initialStock - 5, (float) $this->stockA->fresh()->quantity);

        // Verify total amount recalculated on header
        $this->assertEquals(30000, (float) $sale->fresh()->total_amount);
    }

    public function test_can_update_item_quantity_in_pos_sale_detail(): void
    {
        Sanctum::actingAs($this->user);

        $sale = PosSale::create([
            'transaction_number' => 'TR-OTC-20260902-0002',
            'transaction_date' => Carbon::parse('2026-09-02'),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Person 45655962',
            'status' => 'draft',
        ]);

        // Add 5 items
        $this->postJson("/api/v1/logistics/pos-sales/{$sale->id}/items", [
            'medicine_id' => $this->medicineA->id,
            'warehouse_id' => $this->warehousePusat->id,
            'warehouse_stock_id' => $this->stockA->id,
            'quantity' => 5,
            'price' => 6000,
        ]);

        $item = $sale->fresh()->items()->first();

        // Update qty to 8 (diff: +3 deducted)
        $initialStock = (float) $this->stockA->fresh()->quantity;
        $response = $this->putJson("/api/v1/logistics/pos-sales/{$sale->id}/items/{$item->id}", [
            'quantity' => 8,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.quantity', 8)
            ->assertJsonPath('data.subtotal', 48000);

        $this->assertEquals($initialStock - 3, (float) $this->stockA->fresh()->quantity);
        $this->assertEquals(48000, (float) $sale->fresh()->total_amount);
    }

    public function test_can_delete_item_from_pos_sale_detail_and_restore_stock(): void
    {
        Sanctum::actingAs($this->user);

        $sale = PosSale::create([
            'transaction_number' => 'TR-OTC-20260902-0003',
            'transaction_date' => Carbon::parse('2026-09-02'),
            'warehouse_id' => $this->warehousePusat->id,
            'customer_name' => 'Person 45655962',
            'status' => 'draft',
        ]);

        $initialStock = (float) $this->stockA->fresh()->quantity;

        // Add 5 items
        $this->postJson("/api/v1/logistics/pos-sales/{$sale->id}/items", [
            'medicine_id' => $this->medicineA->id,
            'warehouse_id' => $this->warehousePusat->id,
            'warehouse_stock_id' => $this->stockA->id,
            'quantity' => 5,
            'price' => 6000,
        ]);

        $item = $sale->fresh()->items()->first();

        // Delete item
        $response = $this->deleteJson("/api/v1/logistics/pos-sales/{$sale->id}/items/{$item->id}");
        $response->assertStatus(200);

        // Verify stock is restored
        $this->assertEquals($initialStock, (float) $this->stockA->fresh()->quantity);
        $this->assertEquals(0, (float) $sale->fresh()->total_amount);
    }
}

