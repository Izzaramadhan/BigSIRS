<?php

namespace Tests\Feature\Logistics;

use App\Models\Logistics\StockMovement;
use App\Models\Logistics\StockMutation;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineUnit;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StockMutationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Warehouse $sourceWarehouse;

    protected Warehouse $targetWarehouse;

    protected Medicine $medicineA;

    protected Medicine $medicineB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Apt. Novita Anggraini',
        ]);

        $this->sourceWarehouse = Warehouse::create([
            'code' => 'GDF',
            'name' => 'Gudang Utama',
            'description' => 'Gudang Farmasi Pusat',
        ]);

        $this->targetWarehouse = Warehouse::create([
            'code' => 'AP-RJ',
            'name' => 'Apotek Rawat Jalan',
            'description' => 'Apotek Farmasi Rawat Jalan',
        ]);

        $unit = MedicineUnit::create(['name' => 'Tablet']);

        $this->medicineA = Medicine::create([
            'code' => 'MED-001',
            'name' => 'Paracetamol 500 mg Tablet',
            'medicine_unit_id' => $unit->id,
            'selling_price_per_unit' => 1000,
            'is_active' => true,
        ]);

        $this->medicineB = Medicine::create([
            'code' => 'MED-002',
            'name' => 'Amoxicillin 500 mg Kapsul',
            'medicine_unit_id' => $unit->id,
            'selling_price_per_unit' => 2000,
            'is_active' => true,
        ]);

        // Seed initial stock in source warehouse
        WarehouseStock::create([
            'warehouse_id' => $this->sourceWarehouse->id,
            'medicine_id' => $this->medicineA->id,
            'quantity' => 100.00,
            'batch_number' => 'BATCH-A1',
            'expired_date' => Carbon::now()->addYear()->format('Y-m-d'),
        ]);

        WarehouseStock::create([
            'warehouse_id' => $this->sourceWarehouse->id,
            'medicine_id' => $this->medicineB->id,
            'quantity' => 50.00,
            'batch_number' => 'BATCH-B1',
            'expired_date' => Carbon::now()->addMonths(6)->format('Y-m-d'),
        ]);
    }

    // 1. Endpoint membutuhkan autentikasi.
    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/logistics/mutations')->assertStatus(401);
        $this->getJson('/api/v1/logistics/mutations/summary')->assertStatus(401);
        $this->getJson('/api/v1/logistics/mutations/export')->assertStatus(401);
        $this->getJson('/api/v1/logistics/warehouse-stocks?warehouse_id=1')->assertStatus(401);
        $this->postJson('/api/v1/logistics/mutations', [])->assertStatus(401);
    }

    // 2. Daftar mutasi memakai pagination.
    public function test_mutations_list_uses_pagination(): void
    {
        Sanctum::actingAs($this->user);

        // Create 15 mutations
        for ($i = 1; $i <= 15; $i++) {
            StockMutation::create([
                'mutation_number' => 'MUT-202610-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'mutation_date' => Carbon::now()->subDays(15 - $i),
                'source_warehouse_id' => $this->sourceWarehouse->id,
                'target_warehouse_id' => $this->targetWarehouse->id,
                'notes' => "Mutasi ke-{$i}",
                'status' => 'validated',
                'created_by' => $this->user->id,
                'validated_by' => $this->user->id,
                'validated_at' => Carbon::now(),
            ]);
        }

        $response = $this->getJson('/api/v1/logistics/mutations?per_page=10&page=1');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links',
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(15, $response->json('meta.total'));
    }

    // 3. Filter tanggal bekerja.
    public function test_filter_by_date_range_works(): void
    {
        Sanctum::actingAs($this->user);

        StockMutation::create([
            'mutation_number' => 'MUT-202610-0001',
            'mutation_date' => '2026-10-01 10:00:00',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        StockMutation::create([
            'mutation_number' => 'MUT-202610-0002',
            'mutation_date' => '2026-10-15 10:00:00',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        StockMutation::create([
            'mutation_number' => 'MUT-202610-0003',
            'mutation_date' => '2026-10-25 10:00:00',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/logistics/mutations?start_date=2026-10-10&end_date=2026-10-20');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('MUT-202610-0002', $data[0]['mutationNumber']);
    }

    // 4. Filter gudang asal bekerja.
    public function test_filter_by_source_warehouse_works(): void
    {
        Sanctum::actingAs($this->user);

        $otherWarehouse = Warehouse::create(['name' => 'Apotek IGD', 'code' => 'AP-IGD']);

        StockMutation::create([
            'mutation_number' => 'MUT-202610-0001',
            'mutation_date' => Carbon::now(),
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        StockMutation::create([
            'mutation_number' => 'MUT-202610-0002',
            'mutation_date' => Carbon::now(),
            'source_warehouse_id' => $otherWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/logistics/mutations?source_warehouse_id='.$this->sourceWarehouse->id);
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('MUT-202610-0001', $response->json('data.0.mutationNumber'));
    }

    // 5. Filter gudang tujuan bekerja.
    public function test_filter_by_target_warehouse_works(): void
    {
        Sanctum::actingAs($this->user);

        $otherWarehouse = Warehouse::create(['name' => 'Apotek IGD', 'code' => 'AP-IGD']);

        StockMutation::create([
            'mutation_number' => 'MUT-202610-0001',
            'mutation_date' => Carbon::now(),
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        StockMutation::create([
            'mutation_number' => 'MUT-202610-0002',
            'mutation_date' => Carbon::now(),
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $otherWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/logistics/mutations?target_warehouse_id='.$otherWarehouse->id);
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('MUT-202610-0002', $response->json('data.0.mutationNumber'));
    }

    // 6. Search nomor/catatan/obat bekerja.
    public function test_search_by_number_notes_or_medicine_works(): void
    {
        Sanctum::actingAs($this->user);

        $mutation = StockMutation::create([
            'mutation_number' => 'MUT-202610-9999',
            'mutation_date' => Carbon::now(),
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'notes' => 'Catatan khusus emergency',
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        $mutation->items()->create([
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'quantity' => 10,
            'unit' => 'Tablet',
        ]);

        // Search by mutation number
        $res = $this->getJson('/api/v1/logistics/mutations?search=9999');
        $this->assertCount(1, $res->json('data'));

        // Search by notes keyword
        $res = $this->getJson('/api/v1/logistics/mutations?search=emergency');
        $this->assertCount(1, $res->json('data'));

        // Search by medicine name
        $res = $this->getJson('/api/v1/logistics/mutations?search=Paracetamol');
        $this->assertCount(1, $res->json('data'));
    }

    // 7. Detail mutasi dapat dibaca.
    public function test_mutation_detail_can_be_retrieved(): void
    {
        Sanctum::actingAs($this->user);

        $mutation = StockMutation::create([
            'mutation_number' => 'MUT-202610-0100',
            'mutation_date' => Carbon::now(),
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'notes' => 'Detail test',
            'status' => 'validated',
            'created_by' => $this->user->id,
            'validated_by' => $this->user->id,
            'validated_at' => Carbon::now(),
        ]);

        $mutation->items()->create([
            'medicine_id' => $this->medicineA->id,
            'medicine_name' => $this->medicineA->name,
            'quantity' => 20,
            'unit' => 'Tablet',
        ]);

        $response = $this->getJson("/api/v1/logistics/mutations/{$mutation->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $mutation->id,
                'mutationNumber' => 'MUT-202610-0100',
                'sourceWarehouse' => 'Gudang Utama',
                'targetWarehouse' => 'Apotek Rawat Jalan',
                'itemCount' => 1,
                'totalQuantity' => 20,
                'items' => [
                    [
                        'medicineName' => 'Paracetamol 500 mg Tablet',
                        'quantity' => 20,
                    ],
                ],
            ],
        ]);
    }

    // 8. Mutasi berhasil dibuat.
    public function test_mutation_created_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'mutationDate' => '2026-10-29 09:30:00',
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'notes' => 'Restock harian',
            'items' => [
                [
                    'medicineId' => $this->medicineA->id,
                    'quantity' => 25,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/mutations', $payload);
        $response->assertStatus(201);
        $this->assertDatabaseHas('stock_mutations', [
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'notes' => 'Restock harian',
            'status' => 'validated',
            'total_value' => 25000.00,
        ]);

        $this->assertDatabaseHas('stock_mutation_items', [
            'medicine_id' => $this->medicineA->id,
            'quantity' => 25.00,
            'unit_price' => 1000.00,
            'subtotal' => 25000.00,
        ]);

        $response->assertJson([
            'data' => [
                'totalValue' => 25000,
                'items' => [
                    [
                        'medicineId' => $this->medicineA->id,
                        'unitPrice' => 1000,
                        'subtotal' => 25000,
                    ],
                ],
            ],
        ]);
    }

    // 9. Nomor mutasi dibuat backend dan unique.
    public function test_mutation_number_is_generated_by_backend_and_unique(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'mutationDate' => '2026-10-29 09:30:00',
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => 10],
            ],
        ];

        $res1 = $this->postJson('/api/v1/logistics/mutations', $payload);
        $res1->assertStatus(201);
        $num1 = $res1->json('data.mutationNumber');
        $this->assertMatchesRegularExpression('/^MUT-\d{6}-\d{4}$/', $num1);

        $res2 = $this->postJson('/api/v1/logistics/mutations', $payload);
        $res2->assertStatus(201);
        $num2 = $res2->json('data.mutationNumber');
        $this->assertNotEquals($num1, $num2);
    }

    // 10. Gudang asal sama dengan tujuan ditolak.
    public function test_same_source_and_target_warehouse_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->sourceWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => 10],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/mutations', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target_warehouse_id']);
    }

    // 11. Item kosong ditolak.
    public function test_empty_items_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [],
        ];

        $response = $this->postJson('/api/v1/logistics/mutations', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    // 12. Kuantitas nol/negatif ditolak.
    public function test_zero_or_negative_quantity_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $payloadZero = [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => 0],
            ],
        ];

        $this->postJson('/api/v1/logistics/mutations', $payloadZero)->assertStatus(422);

        $payloadNeg = [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => -5],
            ],
        ];

        $this->postJson('/api/v1/logistics/mutations', $payloadNeg)->assertStatus(422);
    }

    // 13. Obat tidak valid ditolak.
    public function test_invalid_medicine_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => 999999, 'quantity' => 10],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/mutations', $payload);
        $response->assertStatus(422);
    }

    // 14. Stok tidak mencukupi ditolak.
    public function test_insufficient_stock_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => 500], // available: 100
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/mutations', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity']);
    }

    // 15. Stok sumber berkurang sesuai jumlah.
    public function test_source_warehouse_stock_decreases_correctly(): void
    {
        Sanctum::actingAs($this->user);

        $initialStock = (float) WarehouseStock::where('warehouse_id', $this->sourceWarehouse->id)
            ->where('medicine_id', $this->medicineA->id)
            ->sum('quantity');

        $this->postJson('/api/v1/logistics/mutations', [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => 30],
            ],
        ])->assertStatus(201);

        $newStock = (float) WarehouseStock::where('warehouse_id', $this->sourceWarehouse->id)
            ->where('medicine_id', $this->medicineA->id)
            ->sum('quantity');

        $this->assertEquals($initialStock - 30, $newStock);
    }

    // 16. Stok tujuan bertambah sesuai jumlah.
    public function test_target_warehouse_stock_increases_correctly(): void
    {
        Sanctum::actingAs($this->user);

        $initialTarget = (float) WarehouseStock::where('warehouse_id', $this->targetWarehouse->id)
            ->where('medicine_id', $this->medicineA->id)
            ->sum('quantity');

        $this->postJson('/api/v1/logistics/mutations', [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => 30],
            ],
        ])->assertStatus(201);

        $newTarget = (float) WarehouseStock::where('warehouse_id', $this->targetWarehouse->id)
            ->where('medicine_id', $this->medicineA->id)
            ->sum('quantity');

        $this->assertEquals($initialTarget + 30, $newTarget);
    }

    // 17. Ledger OUT dan IN tercatat.
    public function test_out_and_in_stock_movements_are_recorded(): void
    {
        Sanctum::actingAs($this->user);

        $res = $this->postJson('/api/v1/logistics/mutations', [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => 20],
            ],
        ]);
        $res->assertStatus(201);
        $mutationId = $res->json('data.id');

        $movements = StockMovement::where('stock_mutation_id', $mutationId)->get();
        $this->assertCount(2, $movements);

        $out = $movements->where('direction', 'OUT')->first();
        $in = $movements->where('direction', 'IN')->first();

        $this->assertNotNull($out);
        $this->assertNotNull($in);
        $this->assertEquals(20, (float) $out->quantity);
        $this->assertEquals(20, (float) $in->quantity);
        $this->assertEquals($this->sourceWarehouse->id, $out->warehouse_id);
        $this->assertEquals($this->targetWarehouse->id, $in->warehouse_id);
    }

    // 18. created_by berasal dari user login.
    public function test_created_by_is_derived_from_authenticated_user(): void
    {
        Sanctum::actingAs($this->user);

        $res = $this->postJson('/api/v1/logistics/mutations', [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => 10],
            ],
        ]);
        $res->assertStatus(201);

        $mutation = StockMutation::find($res->json('data.id'));
        $this->assertEquals($this->user->id, $mutation->created_by);
        $this->assertEquals($this->user->id, $mutation->validated_by);
    }

    // 19. Jika salah satu item gagal, seluruh transaksi rollback.
    public function test_transaction_rolls_back_if_any_item_fails(): void
    {
        Sanctum::actingAs($this->user);

        $initialStockA = (float) WarehouseStock::where('warehouse_id', $this->sourceWarehouse->id)
            ->where('medicine_id', $this->medicineA->id)
            ->sum('quantity');

        $initialMutationCount = StockMutation::count();
        $initialMovementCount = StockMovement::count();

        // Item 1 valid (qty: 10), Item 2 exceeds stock (qty: 9999)
        $response = $this->postJson('/api/v1/logistics/mutations', [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => 10],
                ['medicineId' => $this->medicineB->id, 'quantity' => 9999],
            ],
        ]);

        $response->assertStatus(422);

        // Verify full rollback: no mutation, no ledger, stock unchanged
        $this->assertEquals($initialMutationCount, StockMutation::count());
        $this->assertEquals($initialMovementCount, StockMovement::count());
        $currentStockA = (float) WarehouseStock::where('warehouse_id', $this->sourceWarehouse->id)
            ->where('medicine_id', $this->medicineA->id)
            ->sum('quantity');
        $this->assertEquals($initialStockA, $currentStockA);
    }

    // 20. Mutasi tervalidasi tidak dapat diubah atau dihapus.
    public function test_validated_mutation_cannot_be_updated_or_deleted(): void
    {
        Sanctum::actingAs($this->user);

        $mutation = StockMutation::create([
            'mutation_number' => 'MUT-202610-0001',
            'mutation_date' => Carbon::now(),
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        // Route for update / delete does not exist or method not allowed
        $this->putJson("/api/v1/logistics/mutations/{$mutation->id}", ['notes' => 'Hack'])->assertStatus(405);
        $this->deleteJson("/api/v1/logistics/mutations/{$mutation->id}")->assertStatus(405);
    }

    // 21. Summary dihitung dari database.
    public function test_summary_is_calculated_from_database(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/logistics/mutations', [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => 10],
            ],
        ])->assertStatus(201);

        $response = $this->getJson('/api/v1/logistics/mutations/summary');
        $response->assertStatus(200);
        $response->assertJson([
            'totalTransactions' => '1 Transaksi',
            'totalCount' => 1,
            'topSource' => 'Gudang Utama',
            'topTarget' => 'Apotek Rawat Jalan',
            'integrity' => '100%',
        ]);
    }

    // 22. Export mengembalikan file CSV.
    public function test_export_returns_csv_file(): void
    {
        Sanctum::actingAs($this->user);

        StockMutation::create([
            'mutation_number' => 'MUT-202610-0001',
            'mutation_date' => Carbon::now(),
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get('/api/v1/logistics/mutations/export');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Daftar_Mutasi_Barang', $response->headers->get('Content-Disposition'));
    }

    // 23. Query tidak menghasilkan N+1 yang tidak wajar.
    public function test_query_does_not_cause_n_plus_one_issue(): void
    {
        Sanctum::actingAs($this->user);

        // Seed 5 mutations
        for ($i = 1; $i <= 5; $i++) {
            $m = StockMutation::create([
                'mutation_number' => 'MUT-202610-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'mutation_date' => Carbon::now(),
                'source_warehouse_id' => $this->sourceWarehouse->id,
                'target_warehouse_id' => $this->targetWarehouse->id,
                'status' => 'validated',
                'created_by' => $this->user->id,
            ]);
            $m->items()->create([
                'medicine_id' => $this->medicineA->id,
                'medicine_name' => $this->medicineA->name,
                'quantity' => 5,
                'unit' => 'Tablet',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/v1/logistics/mutations?per_page=10')->assertStatus(200);

        $queries = DB::getQueryLog();
        // Expect a low, fixed number of queries (pagination count, main query, eager loading for source, target, creator, items, medicines, units)
        $this->assertLessThan(15, count($queries), 'Query count should be controlled and free of N+1');
    }

    // 24. Request duplikat tidak memindahkan stok dua kali.
    public function test_duplicate_items_in_single_request_are_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'sourceWarehouseId' => $this->sourceWarehouse->id,
            'targetWarehouseId' => $this->targetWarehouse->id,
            'items' => [
                ['medicineId' => $this->medicineA->id, 'quantity' => 10],
                ['medicineId' => $this->medicineA->id, 'quantity' => 20],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/mutations', $payload);
        $response->assertStatus(422);
    }

    // 25. Pagination respects per_page options and defaults to 10.
    public function test_pagination_respects_per_page_options_and_defaults_to_10(): void
    {
        Sanctum::actingAs($this->user);

        for ($i = 1; $i <= 30; $i++) {
            StockMutation::create([
                'mutation_number' => 'MUT-PAG-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'mutation_date' => Carbon::now()->subDays($i),
                'source_warehouse_id' => $this->sourceWarehouse->id,
                'target_warehouse_id' => $this->targetWarehouse->id,
                'status' => 'validated',
                'created_by' => $this->user->id,
            ]);
        }

        // Default should be 10
        $resDefault = $this->getJson('/api/v1/logistics/mutations');
        $resDefault->assertStatus(200);
        $this->assertEquals(10, $resDefault->json('meta.per_page'));
        $this->assertCount(10, $resDefault->json('data'));

        // Explicit per_page 25
        $res25 = $this->getJson('/api/v1/logistics/mutations?per_page=25');
        $res25->assertStatus(200);
        $this->assertEquals(25, $res25->json('meta.per_page'));
        $this->assertCount(25, $res25->json('data'));

        // Explicit per_page 50
        $res50 = $this->getJson('/api/v1/logistics/mutations?per_page=50');
        $res50->assertStatus(200);
        $this->assertEquals(50, $res50->json('meta.per_page'));
        $this->assertCount(30, $res50->json('data'));

        // Invalid per_page falls back to 10
        $resInvalid = $this->getJson('/api/v1/logistics/mutations?per_page=77');
        $resInvalid->assertStatus(200);
        $this->assertEquals(10, $resInvalid->json('meta.per_page'));
    }

    // 26. Data diurutkan terbaru terlebih dahulu (mutation_date desc, id desc).
    public function test_mutations_ordered_by_latest_mutation_date_and_id_desc(): void
    {
        Sanctum::actingAs($this->user);

        $m1 = StockMutation::create([
            'mutation_number' => 'MUT-OLD',
            'mutation_date' => '2026-10-01 10:00:00',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        $m2 = StockMutation::create([
            'mutation_number' => 'MUT-NEWEST',
            'mutation_date' => '2026-10-20 10:00:00',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        $m3 = StockMutation::create([
            'mutation_number' => 'MUT-MIDDLE',
            'mutation_date' => '2026-10-10 10:00:00',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        $res = $this->getJson('/api/v1/logistics/mutations');
        $res->assertStatus(200);
        $data = $res->json('data');

        $this->assertEquals('MUT-NEWEST', $data[0]['mutationNumber']);
        $this->assertEquals('MUT-MIDDLE', $data[1]['mutationNumber']);
        $this->assertEquals('MUT-OLD', $data[2]['mutationNumber']);
    }

    // 27. Filter tanggal hanya aktif jika parameter dikirimkan (tidak ada default cutoff).
    public function test_date_filter_only_applies_when_parameters_are_sent(): void
    {
        Sanctum::actingAs($this->user);

        StockMutation::create([
            'mutation_number' => 'MUT-PAST-YEAR',
            'mutation_date' => '2025-01-01 10:00:00',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        StockMutation::create([
            'mutation_number' => 'MUT-CURRENT',
            'mutation_date' => '2026-10-29 10:00:00',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'target_warehouse_id' => $this->targetWarehouse->id,
            'status' => 'validated',
            'created_by' => $this->user->id,
        ]);

        // Without date filter: both mutations are present
        $resAll = $this->getJson('/api/v1/logistics/mutations');
        $resAll->assertStatus(200);
        $this->assertCount(2, $resAll->json('data'));

        // With date filter: only filtered mutation is returned
        $resFiltered = $this->getJson('/api/v1/logistics/mutations?start_date=2026-10-01');
        $resFiltered->assertStatus(200);
        $this->assertCount(1, $resFiltered->json('data'));
        $this->assertEquals('MUT-CURRENT', $resFiltered->json('data.0.mutationNumber'));
    }
}
