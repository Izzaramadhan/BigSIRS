<?php

namespace App\Http\Controllers\Api\V1\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\CancelPosSaleRequest;
use App\Http\Requests\Logistics\StorePosSaleRequest;
use App\Http\Requests\Logistics\UpdatePosSaleRequest;
use App\Http\Resources\Logistics\PosSaleItemResource;
use App\Http\Resources\Logistics\PosSaleResource;
use App\Models\Employee;
use App\Models\Legacy\LegacyGoodsIssue;
use App\Models\Legacy\LegacyGoodsIssueItem;
use App\Models\Legacy\LegacyStockLog;
use App\Models\Legacy\LegacyWarehouse;
use App\Models\Logistics\PosSale;
use App\Models\Logistics\PosSaleItem;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Warehouse;
use App\Services\Logistics\PosSaleService;
use App\Support\LegacyAccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PosSaleController extends Controller
{
    public function __construct(
        protected PosSaleService $service
    ) {}

    /**
     * Display a listing of POS OTC sales with filtering and pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', PosSale::class);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $query = LegacyGoodsIssue::posSale()->with([
                'employee',
                'items.stockLog.warehouse',
                'items.stockLog.fakturDetail.medicine.unit',
            ]);

            $search = $request->get('search') ?? $request->get('q') ?? $request->get('transactionNumber');
            if (! empty($search)) {
                $query->search($search);
            }

            if ($customer = ($request->get('customerName') ?? $request->get('customer_name') ?? $request->get('pembeli'))) {
                $query->where('pembeli', 'like', "%{$customer}%");
            }

            $startDate = $request->get('startDate') ?? $request->get('start_date');
            if (! empty($startDate)) {
                $query->whereDate('tanggal', '>=', $startDate);
            }
            $endDate = $request->get('endDate') ?? $request->get('end_date');
            if (! empty($endDate)) {
                $query->whereDate('tanggal', '<=', $endDate);
            }

            $perPage = (int) ($request->get('per_page') ?? $request->get('perPage') ?? 10);
            $perPage = max(1, min(100, $perPage));

            $sales = $query->orderByDesc('tanggal')->orderByDesc('id')->paginate($perPage);

            return PosSaleResource::collection($sales);
        }

        $query = PosSale::with([
            'warehouse',
            'customer',
            'cashier',
            'items.medicine.unit',
            'items.medicine.category',
            'items.warehouseStock.warehouse',
        ]);

        // Search by keyword (transaction number, customer name, date)
        $search = $request->get('search') ?? $request->get('q') ?? $request->get('transactionNumber');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });

                if (! is_numeric($search) && preg_match('/[\/\-]/', $search)) {
                    try {
                        $parsed = Carbon::parse($search);
                        $q->orWhereDate('transaction_date', $parsed->format('Y-m-d'));
                    } catch (\Throwable $e) {
                        // Ignore date parse errors
                    }
                }
            });
        }

        // Customer / Pembeli filter
        $customer = $request->get('customerName') ?? $request->get('customer_name') ?? $request->get('pembeli');
        if (! empty($customer)) {
            $query->where(function ($q) use ($customer) {
                $q->where('customer_name', 'like', "%{$customer}%")
                    ->orWhereHas('customer', function ($cq) use ($customer) {
                        $cq->where('name', 'like', "%{$customer}%");
                    });
            });
        }

        // Date Range
        $startDate = $request->get('startDate') ?? $request->get('start_date');
        if (! empty($startDate)) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }
        $endDate = $request->get('endDate') ?? $request->get('end_date');
        if (! empty($endDate)) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        // Location / Warehouse filter
        $location = $request->get('location') ?? $request->get('warehouseId') ?? $request->get('warehouse_id');
        if (! empty($location) && $location !== 'Semua Lokasi') {
            if (is_numeric($location)) {
                $query->where('warehouse_id', (int) $location);
            } else {
                $query->whereHas('warehouse', function ($q) use ($location) {
                    $q->where('name', 'like', "%{$location}%");
                });
            }
        }

        // Status filter
        $status = $request->get('status');
        if (! empty($status) && $status !== 'Semua Status') {
            $dbStatus = match (strtolower($status)) {
                'lunas', 'selesai', 'completed' => 'completed',
                'draft' => 'draft',
                'dibatalkan', 'void', 'cancelled' => 'cancelled',
                default => $status,
            };
            $query->where('status', $dbStatus);
        }

        // Sorting: default newest transaction first
        $sortBy = $request->get('sortBy', 'transaction_date');
        $sortDir = strtolower($request->get('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['transaction_date', 'transaction_number', 'total_amount', 'created_at', 'status'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir)->orderBy('id', 'desc');
        } else {
            $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');
        }

        $perPage = (int) ($request->get('per_page') ?? $request->get('perPage') ?? 10);
        $perPage = max(1, min(100, $perPage));

        $sales = $query->paginate($perPage);

        return PosSaleResource::collection($sales);
    }

    /**
     * Search master pembeli (customers / employees).
     */
    public function customers(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PosSale::class);

        $query = Employee::where('is_active', true);

        if ($search = $request->get('search') ?? $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        $limit = max(5, min(50, (int) $request->get('limit', 20)));
        $customers = $query->orderBy('name')->limit($limit)->get(['id', 'name', 'code']);

        return response()->json([
            'status' => 'success',
            'data' => $customers,
        ]);
    }

    /**
     * Get summary statistics for the POS OTC sales dashboard.
     */
    public function summary(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PosSale::class);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $stats = DB::connection('legacy')->table('lgs_pengeluaran_detail as d')
                ->join('lgs_pengeluaran as p', 'd.id_lgs_pengeluaran', '=', 'p.id')
                ->join('lgs_log_stok as s', 'd.id_lgs_log_stok', '=', 's.id')
                ->where('p.flag', 'apotek_bebas')
                ->where('p.status', 1)
                ->whereNull('p.deleted_at')
                ->selectRaw('COUNT(DISTINCT p.id) as total_transactions, COALESCE(SUM(d.jumlah * s.harga_jual), 0) as total_sales')
                ->first();

            $totalSales = (float) ($stats->total_sales ?? 0);
            $totalTransactions = (int) ($stats->total_transactions ?? 0);
            $totalItemsSold = (int) DB::connection('legacy')->table('lgs_pengeluaran_detail as d')
                ->join('lgs_pengeluaran as p', 'd.id_lgs_pengeluaran', '=', 'p.id')
                ->where('p.flag', 'apotek_bebas')
                ->where('p.status', 1)
                ->whereNull('p.deleted_at')
                ->sum('d.jumlah');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_sales' => $totalSales,
                    'total_transactions' => $totalTransactions,
                    'average_transaction_value' => $totalTransactions > 0 ? round($totalSales / $totalTransactions, 2) : 0,
                    'total_items_sold' => $totalItemsSold,
                ],
            ]);
        }

        $summary = $this->service->getSummary();

        return response()->json([
            'status' => 'success',
            'data' => $summary,
        ]);
    }

    /**
     * Get form options (locations, payment methods, customer list, etc.).
     */
    public function formOptions(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PosSale::class);

        if (LegacyAccess::isAvailable('lgs_log_stok')) {
            $warehouses = LegacyWarehouse::orderBy('nama')->get();
            $locations = ['Semua Lokasi'];
            foreach ($warehouses as $w) {
                $locations[] = $w->name;
            }

            $paymentMethods = [
                'Semua Metode',
                'Tunai',
                'QRIS',
                'Debit BCA',
                'Debit Mandiri',
            ];

            $statuses = [
                'Semua Status',
                'Lunas',
                'Draft',
                'Dibatalkan',
            ];

            $customerTypes = [
                'Umum',
                'Staf RS',
                'Pasien',
            ];

            $stocks = LegacyStockLog::with(['warehouse', 'fakturDetail.medicine.unit'])
                ->where('qty', '>', 0)
                ->limit(50)
                ->get();

            $otcMedicines = [];
            $seenMedicines = [];
            foreach ($stocks as $s) {
                $med = $s->medicine;
                if (! $med) continue;
                if (isset($seenMedicines[$s->medicine_id])) continue;
                $seenMedicines[$s->medicine_id] = true;

                $otcMedicines[] = [
                    'id' => $med->id,
                    'warehouseStockId' => $s->id,
                    'name' => $med->name,
                    'category' => 'Obat Bebas',
                    'price' => (float) $s->selling_price,
                    'unit' => $med->unit?->name ?? 'Pcs',
                    'availableStock' => (float) $s->qty,
                    'batchNumber' => $s->batch_number,
                    'expiredDate' => $s->expired_date,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'locations' => $locations,
                    'paymentMethods' => $paymentMethods,
                    'statuses' => $statuses,
                    'customerTypes' => $customerTypes,
                    'customers' => [],
                    'warehouses' => $warehouses->map(fn ($w) => ['id' => $w->id, 'name' => $w->name, 'code' => $w->code]),
                    'otcMedicines' => $otcMedicines,
                ],
            ]);
        }

        $warehouses = Warehouse::orderBy('name')->get();
        $locations = ['Semua Lokasi'];
        foreach ($warehouses as $w) {
            $locations[] = $w->name;
        }

        $paymentMethods = [
            'Semua Metode',
            'Tunai',
            'QRIS',
            'Debit BCA',
            'Debit Mandiri',
        ];

        $statuses = [
            'Semua Status',
            'Lunas',
            'Draft',
            'Dibatalkan',
        ];

        $customerTypes = [
            'Umum',
            'Staf RS',
            'Pasien',
        ];

        // Fetch master pembeli sample
        $customers = Employee::where('is_active', true)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'code']);

        // Fetch top OTC medicines / stocks for convenient selection
        $stocks = WarehouseStock::with(['medicine.unit', 'medicine.category', 'warehouse'])
            ->where('quantity', '>', 0)
            ->limit(50)
            ->get();

        $otcMedicines = [];
        $seenMedicines = [];
        foreach ($stocks as $s) {
            if (! $s->medicine) continue;
            if (isset($seenMedicines[$s->medicine_id])) continue;
            $seenMedicines[$s->medicine_id] = true;

            $otcMedicines[] = [
                'id' => $s->medicine->id,
                'warehouseStockId' => $s->id,
                'name' => $s->medicine->name,
                'category' => $s->medicine->category?->name ?? 'Obat Bebas',
                'price' => (float) ($s->selling_price > 0 ? $s->selling_price : ($s->medicine->selling_price_per_unit ?? 10000)),
                'unit' => $s->medicine->unit?->name ?? 'Pcs',
                'availableStock' => (float) $s->quantity,
                'batchNumber' => $s->batch_number,
                'expiredDate' => $s->expired_date ? $s->expired_date->format('Y-m-d') : null,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'locations' => $locations,
                'paymentMethods' => $paymentMethods,
                'statuses' => $statuses,
                'customerTypes' => $customerTypes,
                'customers' => $customers,
                'warehouses' => $warehouses->map(fn ($w) => ['id' => $w->id, 'name' => $w->name, 'code' => $w->code]),
                'otcMedicines' => $otcMedicines,
            ],
        ]);
    }

    /**
     * Store a newly created POS OTC sale header in storage.
     */
    public function store(StorePosSaleRequest $request): JsonResponse
    {
        Gate::authorize('create', PosSale::class);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $validated = $request->validated();
            $legacyUserId = $request->user()?->getLegacyUserId() ?? 1;

            $sale = DB::connection('legacy')->transaction(function () use ($validated, $legacyUserId, $request) {
                $isDraft = filter_var($request->input('is_draft', $request->input('isDraft', false)), FILTER_VALIDATE_BOOLEAN);
                $saleDate = $validated['transaction_date'] ?? date('Y-m-d');
                $customerName = $validated['customer_name'] ?? 'Pasien Umum';
                $paymentMethod = $validated['payment_method'] ?? 'Tunai';

                $legacySale = LegacyGoodsIssue::create([
                    'tanggal' => $saleDate,
                    'id_pegawai' => $validated['cashier_id'] ?? 1,
                    'pembeli' => $customerName,
                    'flag' => 'apotek_bebas',
                    'cara_bayar' => strtolower($paymentMethod) === 'non tunai' ? 'non tunai' : 'tunai',
                    'status_lunas' => 'sudah bayar',
                    'id_user' => $legacyUserId,
                    'status' => $isDraft ? 0 : 1,
                    'kode_billing' => $validated['transaction_number'] ?? null,
                ]);

                foreach ($validated['items'] ?? [] as $item) {
                    $stockId = (int) ($item['warehouse_stock_id'] ?? $item['warehouseStockId'] ?? 0);
                    $qty = (float) ($item['quantity'] ?? 0);

                    LegacyGoodsIssueItem::create([
                        'id_lgs_pengeluaran' => $legacySale->id,
                        'id_lgs_log_stok' => $stockId,
                        'jumlah' => $qty,
                        'status_lunas' => 'sudah bayar',
                        'id_user' => $legacyUserId,
                        'status' => 1,
                    ]);

                    if (! $isDraft && $stockId) {
                        $stock = LegacyStockLog::lockForUpdate()->find($stockId);
                        if ($stock) {
                            $stock->qty = max(0, $stock->qty - $qty);
                            $stock->save();
                        }
                    }
                }

                return $legacySale;
            });

            return (new PosSaleResource($sale->load(['employee', 'items.stockLog.warehouse', 'items.stockLog.fakturDetail.medicine.unit'])))
                ->response()
                ->setStatusCode(201);
        }

        $isDraft = filter_var($request->input('is_draft', $request->input('isDraft', false)), FILTER_VALIDATE_BOOLEAN);
        $userId = $request->user()?->id ?? 1;

        $sale = $this->service->createSale($request->validated(), $userId, $isDraft);

        return (new PosSaleResource($sale))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified POS sale with detail items.
     */
    public function show($posSale): PosSaleResource
    {
        $id = $posSale instanceof PosSale ? $posSale->id : ($posSale instanceof LegacyGoodsIssue ? $posSale->id : $posSale);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacy = LegacyGoodsIssue::posSale()->with([
                'employee',
                'items.stockLog.warehouse',
                'items.stockLog.fakturDetail.medicine.unit',
            ])->find($id);

            if ($legacy) {
                return new PosSaleResource($legacy);
            }
        }

        $local = PosSale::with([
            'warehouse',
            'customer',
            'cashier',
            'canceller',
            'items.medicine.unit',
            'items.medicine.category',
            'items.warehouseStock.warehouse',
        ])->findOrFail($id);

        Gate::authorize('view', $local);

        return new PosSaleResource($local);
    }

    /**
     * Update the specified sale header (or draft) in storage.
     */
    public function update(UpdatePosSaleRequest $request, $posSale): PosSaleResource
    {
        $id = $posSale instanceof PosSale ? $posSale->id : ($posSale instanceof LegacyGoodsIssue ? $posSale->id : $posSale);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacy = LegacyGoodsIssue::posSale()->findOrFail($id);
            $validated = $request->validated();
            $legacyUserId = $request->user()?->getLegacyUserId() ?? 1;

            $legacy->update([
                'tanggal' => $validated['transaction_date'] ?? $legacy->tanggal,
                'pembeli' => $validated['customer_name'] ?? $legacy->pembeli,
                'id_user' => $legacyUserId,
            ]);

            return new PosSaleResource($legacy->load(['employee', 'items.stockLog.warehouse', 'items.stockLog.fakturDetail.medicine.unit']));
        }

        $local = PosSale::findOrFail($id);
        Gate::authorize('update', $local);

        $userId = $request->user()?->id ?? 1;

        // If items are provided in update draft request, updateDraft
        if ($local->isDraft() && $request->has('items')) {
            $updated = $this->service->updateDraft($local, $request->validated(), $userId);
        } else {
            // Otherwise, update header info (Tanggal, Pembeli, etc.)
            $updated = $this->service->updateHeader($local, $request->validated(), $userId);
        }

        return new PosSaleResource($updated);
    }

    /**
     * Add single item to sale detail.
     */
    public function addItem(Request $request, $posSale): JsonResponse
    {
        $id = $posSale instanceof PosSale ? $posSale->id : ($posSale instanceof LegacyGoodsIssue ? $posSale->id : $posSale);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $request->validate([
                'medicine_id' => ['required', 'integer'],
                'quantity' => ['required', 'numeric', 'min:0.01'],
                'warehouse_stock_id' => ['nullable', 'integer'],
            ]);

            $legacyUserId = $request->user()?->getLegacyUserId() ?? 1;
            $stockId = (int) ($request->input('warehouse_stock_id') ?? $request->input('stock_id'));
            $qty = (float) $request->input('quantity');

            $item = DB::connection('legacy')->transaction(function () use ($id, $stockId, $qty, $legacyUserId) {
                $detail = LegacyGoodsIssueItem::create([
                    'id_lgs_pengeluaran' => $id,
                    'id_lgs_log_stok' => $stockId,
                    'jumlah' => $qty,
                    'status_lunas' => 'sudah bayar',
                    'id_user' => $legacyUserId,
                    'status' => 1,
                ]);

                $stock = LegacyStockLog::lockForUpdate()->find($stockId);
                if ($stock) {
                    $stock->qty = max(0, $stock->qty - $qty);
                    $stock->save();
                }

                return $detail;
            });

            return (new PosSaleItemResource($item))
                ->response()
                ->setStatusCode(201);
        }

        $local = PosSale::findOrFail($id);
        Gate::authorize('update', $local);

        $request->validate([
            'medicine_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'warehouse_id' => ['nullable', 'integer'],
            'stock_id' => ['nullable', 'integer'],
            'warehouse_stock_id' => ['nullable', 'integer'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'medicine_id.required' => 'Nama sediaan obat wajib dipilih.',
            'quantity.required' => 'Jumlah sediaan harus diisi.',
            'quantity.min' => 'Jumlah sediaan harus lebih besar dari 0.',
        ]);

        $userId = $request->user()?->id ?? 1;
        $item = $this->service->addItem($local, $request->all(), $userId);

        return (new PosSaleItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update single item in sale detail.
     */
    public function updateItem(Request $request, $posSale, $item): JsonResponse
    {
        $id = $posSale instanceof PosSale ? $posSale->id : ($posSale instanceof LegacyGoodsIssue ? $posSale->id : $posSale);
        $itemId = $item instanceof PosSaleItem ? $item->id : ($item instanceof LegacyGoodsIssueItem ? $item->id : $item);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $request->validate([
                'quantity' => ['required', 'numeric', 'min:0.01'],
            ]);

            $legacyUserId = $request->user()?->getLegacyUserId() ?? 1;
            $newQty = (float) $request->input('quantity');

            $updated = DB::connection('legacy')->transaction(function () use ($itemId, $newQty, $legacyUserId) {
                $detail = LegacyGoodsIssueItem::findOrFail($itemId);
                $diff = $newQty - (float) $detail->jumlah;

                $detail->update([
                    'jumlah' => $newQty,
                    'id_user' => $legacyUserId,
                ]);

                if ($detail->id_lgs_log_stok && abs($diff) > 0.0001) {
                    $stock = LegacyStockLog::lockForUpdate()->find($detail->id_lgs_log_stok);
                    if ($stock) {
                        $stock->qty = max(0, $stock->qty - $diff);
                        $stock->save();
                    }
                }

                return $detail;
            });

            return (new PosSaleItemResource($updated))
                ->response()
                ->setStatusCode(200);
        }

        $local = PosSale::findOrFail($id);
        $localItem = PosSaleItem::findOrFail($itemId);
        Gate::authorize('update', $local);

        $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'quantity.required' => 'Jumlah sediaan harus diisi.',
            'quantity.min' => 'Jumlah sediaan harus lebih besar dari 0.',
        ]);

        $userId = $request->user()?->id ?? 1;
        $updated = $this->service->updateItem($local, $localItem, $request->all(), $userId);

        return (new PosSaleItemResource($updated))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Delete single item from sale detail.
     */
    public function deleteItem(Request $request, $posSale, $item): JsonResponse
    {
        $id = $posSale instanceof PosSale ? $posSale->id : ($posSale instanceof LegacyGoodsIssue ? $posSale->id : $posSale);
        $itemId = $item instanceof PosSaleItem ? $item->id : ($item instanceof LegacyGoodsIssueItem ? $item->id : $item);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            DB::connection('legacy')->transaction(function () use ($itemId) {
                $detail = LegacyGoodsIssueItem::findOrFail($itemId);
                if ($detail->id_lgs_log_stok) {
                    $stock = LegacyStockLog::lockForUpdate()->find($detail->id_lgs_log_stok);
                    if ($stock) {
                        $stock->qty += (float) $detail->jumlah;
                        $stock->save();
                    }
                }
                $detail->delete();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Detail sediaan berhasil dihapus dari transaksi.',
            ]);
        }

        $local = PosSale::findOrFail($id);
        $localItem = PosSaleItem::findOrFail($itemId);
        Gate::authorize('update', $local);

        $userId = $request->user()?->id ?? 1;
        $this->service->deleteItem($local, $localItem, $userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail sediaan berhasil dihapus dari transaksi.',
        ]);
    }

    /**
     * Void / Cancel a completed or draft POS sale.
     */
    public function cancel(CancelPosSaleRequest $request, $posSale): PosSaleResource
    {
        $id = $posSale instanceof PosSale ? $posSale->id : ($posSale instanceof LegacyGoodsIssue ? $posSale->id : $posSale);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacy = LegacyGoodsIssue::posSale()->findOrFail($id);
            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->update(['status' => 0]);
                foreach ($legacy->items as $item) {
                    if ($item->id_lgs_log_stok) {
                        $stock = LegacyStockLog::lockForUpdate()->find($item->id_lgs_log_stok);
                        if ($stock) {
                            $stock->qty += (float) $item->jumlah;
                            $stock->save();
                        }
                    }
                }
            });

            return new PosSaleResource($legacy->load(['employee', 'items.stockLog.warehouse', 'items.stockLog.fakturDetail.medicine.unit']));
        }

        $local = PosSale::findOrFail($id);
        Gate::authorize('cancel', $local);

        $reason = $request->input('reason');
        $userId = $request->user()?->id ?? 1;

        $cancelled = $this->service->cancelSale($local, $reason, $userId);

        return new PosSaleResource($cancelled);
    }

    /**
     * Remove sale and restore stock.
     */
    public function destroy($posSale): JsonResponse
    {
        $id = $posSale instanceof PosSale ? $posSale->id : ($posSale instanceof LegacyGoodsIssue ? $posSale->id : $posSale);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $legacy = LegacyGoodsIssue::posSale()->findOrFail($id);
            DB::connection('legacy')->transaction(function () use ($legacy) {
                foreach ($legacy->items as $item) {
                    if ($item->id_lgs_log_stok) {
                        $stock = LegacyStockLog::lockForUpdate()->find($item->id_lgs_log_stok);
                        if ($stock) {
                            $stock->qty += (float) $item->jumlah;
                            $stock->save();
                        }
                    }
                }
                $legacy->items()->delete();
                $legacy->delete();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data penjualan bebas berhasil dihapus.',
            ]);
        }

        $local = PosSale::findOrFail($id);
        Gate::authorize('delete', $local);

        $userId = request()->user()?->id ?? 1;
        $this->service->deleteSaleWithStockRestore($local, $userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Data penjualan bebas berhasil dihapus.',
        ]);
    }

    /**
     * Export POS sales to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', PosSale::class);

        if (LegacyAccess::isAvailable('lgs_pengeluaran')) {
            $query = LegacyGoodsIssue::posSale()->with([
                'employee',
                'items.stockLog.warehouse',
                'items.stockLog.fakturDetail.medicine.unit',
            ])->orderByDesc('tanggal')->orderByDesc('id');

            $filename = 'Daftar_Penjualan_Bebas_' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            return response()->stream(function () use ($query) {
                $handle = fopen('php://output', 'w');
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

                fputcsv($handle, [
                    'No.',
                    'No. Transaksi',
                    'Tanggal',
                    'Pembeli',
                    'Jml Item',
                    'Total Bayar',
                    'Status',
                    'Gudang',
                ]);

                $idx = 1;
                $query->chunk(100, function ($sales) use ($handle, &$idx) {
                    foreach ($sales as $sale) {
                        $status = match ($sale->status) {
                            'completed' => 'Lunas',
                            'cancelled' => 'Dibatalkan',
                            default => 'Draft',
                        };

                        fputcsv($handle, [
                            $idx++,
                            $sale->transaction_number,
                            $sale->tanggal ? Carbon::parse($sale->tanggal)->format('d-m-Y') : '-',
                            $sale->customer_name,
                            $sale->items->count(),
                            $sale->total,
                            $status,
                            $sale->warehouse_name ?? 'Gudang Utama',
                        ]);
                    }
                });

                fclose($handle);
            }, 200, $headers);
        }

        $query = PosSale::with(['warehouse', 'customer', 'cashier', 'items'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }
        if ($startDate = $request->get('startDate')) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }
        if ($endDate = $request->get('endDate')) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        $filename = 'Daftar_Penjualan_Bebas_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'No.',
                'No. Transaksi',
                'Tanggal',
                'Pembeli',
                'Jml Item',
                'Total Bayar',
                'Status',
                'Gudang',
            ]);

            $idx = 1;
            $query->chunk(100, function ($sales) use ($handle, &$idx) {
                foreach ($sales as $sale) {
                    $status = match ($sale->status) {
                        'completed' => 'Lunas',
                        'cancelled' => 'Dibatalkan',
                        'draft' => 'Draft',
                        default => $sale->status,
                    };

                    fputcsv($handle, [
                        $idx++,
                        $sale->transaction_number,
                        $sale->transaction_date ? $sale->transaction_date->format('d-m-Y') : '-',
                        $sale->customer?->name ?? $sale->customer_name,
                        $sale->items->count(),
                        $sale->total_amount,
                        $status,
                        $sale->warehouse?->name ?? '-',
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
