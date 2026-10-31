<?php

namespace App\Http\Controllers\Api\V1\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\CancelPosSaleRequest;
use App\Http\Requests\Logistics\StorePosSaleRequest;
use App\Http\Requests\Logistics\UpdatePosSaleRequest;
use App\Http\Resources\Logistics\PosSaleItemResource;
use App\Http\Resources\Logistics\PosSaleResource;
use App\Models\Employee;
use App\Models\Logistics\PosSale;
use App\Models\Logistics\PosSaleItem;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Warehouse;
use App\Services\Logistics\PosSaleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
    public function show(PosSale $posSale): PosSaleResource
    {
        Gate::authorize('view', $posSale);

        return new PosSaleResource(
            $posSale->load([
                'warehouse',
                'customer',
                'cashier',
                'canceller',
                'items.medicine.unit',
                'items.medicine.category',
                'items.warehouseStock.warehouse',
            ])
        );
    }

    /**
     * Update the specified sale header (or draft) in storage.
     */
    public function update(UpdatePosSaleRequest $request, PosSale $posSale): PosSaleResource
    {
        Gate::authorize('update', $posSale);

        $userId = $request->user()?->id ?? 1;

        // If items are provided in update draft request, updateDraft
        if ($posSale->isDraft() && $request->has('items')) {
            $updated = $this->service->updateDraft($posSale, $request->validated(), $userId);
        } else {
            // Otherwise, update header info (Tanggal, Pembeli, etc.)
            $updated = $this->service->updateHeader($posSale, $request->validated(), $userId);
        }

        return new PosSaleResource($updated);
    }

    /**
     * Add single item to sale detail.
     */
    public function addItem(Request $request, PosSale $posSale): JsonResponse
    {
        Gate::authorize('update', $posSale);

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
        $item = $this->service->addItem($posSale, $request->all(), $userId);

        return (new PosSaleItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update single item in sale detail.
     */
    public function updateItem(Request $request, PosSale $posSale, PosSaleItem $item): JsonResponse
    {
        Gate::authorize('update', $posSale);

        $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'quantity.required' => 'Jumlah sediaan harus diisi.',
            'quantity.min' => 'Jumlah sediaan harus lebih besar dari 0.',
        ]);

        $userId = $request->user()?->id ?? 1;
        $updated = $this->service->updateItem($posSale, $item, $request->all(), $userId);

        return (new PosSaleItemResource($updated))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Delete single item from sale detail.
     */
    public function deleteItem(Request $request, PosSale $posSale, PosSaleItem $item): JsonResponse
    {
        Gate::authorize('update', $posSale);

        $userId = $request->user()?->id ?? 1;
        $this->service->deleteItem($posSale, $item, $userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail sediaan berhasil dihapus dari transaksi.',
        ]);
    }

    /**
     * Void / Cancel a completed or draft POS sale.
     */
    public function cancel(CancelPosSaleRequest $request, PosSale $posSale): PosSaleResource
    {
        Gate::authorize('cancel', $posSale);

        $reason = $request->input('reason');
        $userId = $request->user()?->id ?? 1;

        $cancelled = $this->service->cancelSale($posSale, $reason, $userId);

        return new PosSaleResource($cancelled);
    }

    /**
     * Remove sale and restore stock.
     */
    public function destroy(PosSale $posSale): JsonResponse
    {
        Gate::authorize('delete', $posSale);

        $userId = request()->user()?->id ?? 1;
        $this->service->deleteSaleWithStockRestore($posSale, $userId);

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
