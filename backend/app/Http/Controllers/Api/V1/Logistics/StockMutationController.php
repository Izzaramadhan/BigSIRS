<?php

namespace App\Http\Controllers\Api\V1\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\StoreStockMutationRequest;
use App\Http\Resources\Logistics\StockMutationResource;
use App\Http\Resources\Logistics\WarehouseStockResource;
use App\Models\Logistics\GoodsIssue;
use App\Models\Logistics\PosSale;
use App\Models\Logistics\StockMutation;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Warehouse;
use App\Services\Logistics\StockMutationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockMutationController extends Controller
{
    /**
     * Display a listing of stock mutations with filters, search, and pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', StockMutation::class);

        $query = StockMutation::with([
            'sourceWarehouse',
            'targetWarehouse',
            'creator',
            'validator',
            'items.medicine.unit',
        ])
            ->withCount('items')
            ->withSum('items', 'quantity');

        // 1. Text Search across mutation number, notes, creator name, medicine names
        if ($search = $request->input('search', $request->input('keyword'))) {
            $query->where(function ($q) use ($search) {
                $q->where('mutation_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('medicine_name', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Specific field filters
        $startDate = $request->input('start_date', $request->input('startDate'));
        if ($startDate) {
            $query->whereDate('mutation_date', '>=', $startDate);
        }

        $endDate = $request->input('end_date', $request->input('endDate'));
        if ($endDate) {
            $query->whereDate('mutation_date', '<=', $endDate);
        }

        $sourceWarehouseId = $request->input('source_warehouse_id', $request->input('sourceWarehouseId', $request->input('sourceWarehouse')));
        if ($sourceWarehouseId) {
            if (is_numeric($sourceWarehouseId)) {
                $query->where('source_warehouse_id', (int) $sourceWarehouseId);
            } else {
                $query->whereHas('sourceWarehouse', fn ($q) => $q->where('name', $sourceWarehouseId));
            }
        }

        $targetWarehouseId = $request->input('target_warehouse_id', $request->input('targetWarehouseId', $request->input('targetWarehouse')));
        if ($targetWarehouseId) {
            if (is_numeric($targetWarehouseId)) {
                $query->where('target_warehouse_id', (int) $targetWarehouseId);
            } else {
                $query->whereHas('targetWarehouse', fn ($q) => $q->where('name', $targetWarehouseId));
            }
        }

        // 3. Sorting
        $column = $request->input('sort', $request->input('sortBy', 'mutation_date'));
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $columnMap = [
            'mutationDate' => 'mutation_date',
            'mutation_date' => 'mutation_date',
            'mutationNumber' => 'mutation_number',
            'mutation_number' => 'mutation_number',
            'sourceWarehouse' => 'source_warehouse_id',
            'source_warehouse_id' => 'source_warehouse_id',
            'targetWarehouse' => 'target_warehouse_id',
            'target_warehouse_id' => 'target_warehouse_id',
            'createdAt' => 'created_at',
            'created_at' => 'created_at',
            'id' => 'id',
        ];

        $sortColumn = $columnMap[$column] ?? 'mutation_date';
        $query->orderBy($sortColumn, $direction);

        if ($sortColumn !== 'id') {
            $query->orderBy('id', 'desc');
        }

        $perPage = (int) $request->input('per_page', $request->input('perPage', 10));
        $perPage = in_array($perPage, [10, 25, 50, 100], true)
            ? $perPage
            : 10;

        return StockMutationResource::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }

    /**
     * Display statistical summary cards for stock mutations.
     */
    public function summary(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', StockMutation::class);

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Total mutations this month
        $currentMonthMutations = StockMutation::whereBetween('mutation_date', [$startOfMonth, $endOfMonth])->get();
        $totalMonthCount = $currentMonthMutations->count();

        // Estimated total value (if medicines have price)
        $estimatedValue = DB::table('stock_mutation_items')
            ->join('medicines', 'stock_mutation_items.medicine_id', '=', 'medicines.id')
            ->join('stock_mutations', 'stock_mutation_items.stock_mutation_id', '=', 'stock_mutations.id')
            ->whereBetween('stock_mutations.mutation_date', [$startOfMonth, $endOfMonth])
            ->whereNotNull('medicines.selling_price_per_unit')
            ->where('medicines.selling_price_per_unit', '>', 0)
            ->sum(DB::raw('stock_mutation_items.quantity * medicines.selling_price_per_unit'));

        $estimatedValueDisplay = $estimatedValue > 0
            ? 'Rp'.number_format((float) $estimatedValue, 0, ',', '.')
            : null;

        // Top source warehouse
        $topSourceRow = StockMutation::select('source_warehouse_id', DB::raw('count(*) as aggregate'))
            ->whereBetween('mutation_date', [$startOfMonth, $endOfMonth])
            ->groupBy('source_warehouse_id')
            ->orderByDesc('aggregate')
            ->first();

        $topSourceWarehouse = $topSourceRow ? Warehouse::find($topSourceRow->source_warehouse_id) : null;
        $topSourceName = $topSourceWarehouse ? $topSourceWarehouse->name : '-';
        $topSourceCount = $topSourceRow ? (int) $topSourceRow->aggregate : 0;
        $topSourcePercent = $totalMonthCount > 0 ? round(($topSourceCount / $totalMonthCount) * 100, 1) : 0;
        $topSourceSubtitle = $topSourceCount > 0
            ? "{$topSourceCount} mutasi keluar ({$topSourcePercent}% volume)"
            : 'Belum ada mutasi keluar';

        // Top target warehouse
        $topTargetRow = StockMutation::select('target_warehouse_id', DB::raw('count(*) as aggregate'))
            ->whereBetween('mutation_date', [$startOfMonth, $endOfMonth])
            ->groupBy('target_warehouse_id')
            ->orderByDesc('aggregate')
            ->first();

        $topTargetWarehouse = $topTargetRow ? Warehouse::find($topTargetRow->target_warehouse_id) : null;
        $topTargetName = $topTargetWarehouse ? $topTargetWarehouse->name : '-';
        $topTargetCount = $topTargetRow ? (int) $topTargetRow->aggregate : 0;
        $topTargetSubtitle = $topTargetCount > 0
            ? "{$topTargetCount} mutasi masuk diproses"
            : 'Belum ada mutasi masuk';

        // Ledger integrity check:
        // 1. Check for negative stocks in warehouse_stocks
        $hasNegativeStocks = WarehouseStock::where('quantity', '<', 0)->exists();

        // 2. Check for unbalanced movements in stock_mutations
        $unbalancedCount = DB::table('stock_mutation_items')
            ->leftJoin('stock_movements as mov_out', function ($join) {
                $join->on('stock_mutation_items.id', '=', 'mov_out.stock_mutation_item_id')
                    ->where('mov_out.direction', '=', 'OUT');
            })
            ->leftJoin('stock_movements as mov_in', function ($join) {
                $join->on('stock_mutation_items.id', '=', 'mov_in.stock_mutation_item_id')
                    ->where('mov_in.direction', '=', 'IN');
            })
            ->where(function ($q) {
                $q->whereNull('mov_out.id')
                    ->orWhereNull('mov_in.id')
                    ->orWhereRaw('mov_out.quantity != mov_in.quantity');
            })
            ->count();

        $isIntegrityValid = ! $hasNegativeStocks && $unbalancedCount === 0;

        return response()->json([
            'totalTransactions' => "{$totalMonthCount} Transaksi",
            'totalCount' => $totalMonthCount,
            'estimatedValue' => $estimatedValueDisplay,
            'topSource' => $topSourceName,
            'topSourceCount' => $topSourceCount,
            'topSourceSubtitle' => $topSourceSubtitle,
            'topTarget' => $topTargetName,
            'topTargetCount' => $topTargetCount,
            'topTargetSubtitle' => $topTargetSubtitle,
            'integrity' => $isIntegrityValid ? '100%' : 'Mismatch',
            'integrityBadge' => $isIntegrityValid ? 'Tersinkronisasi' : 'Perlu Audit',
            'integritySubtitle' => $isIntegrityValid
                ? 'Audit log sinkron tanpa selisih ledger'
                : 'Ditemukan selisih saldo atau ledger tidak seimbang',
        ]);
    }

    /**
     * Store a newly created stock mutation.
     */
    public function store(StoreStockMutationRequest $request, StockMutationService $service): JsonResponse
    {
        Gate::authorize('create', StockMutation::class);

        $mutation = $service->createMutation($request->validated(), (int) $request->user()->id);

        return (new StockMutationResource($mutation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified stock mutation detail.
     */
    public function show(StockMutation $mutation): JsonResponse
    {
        Gate::authorize('view', $mutation);

        $mutation->load([
            'sourceWarehouse',
            'targetWarehouse',
            'creator',
            'validator',
            'items.medicine.unit',
        ]);

        return (new StockMutationResource($mutation))->response();
    }

    /**
     * Export stock mutations list to CSV file.
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', StockMutation::class);

        $query = StockMutation::with([
            'sourceWarehouse',
            'targetWarehouse',
            'creator',
            'items',
        ]);

        // Apply filters
        if ($search = $request->input('search', $request->input('keyword'))) {
            $query->where(function ($q) use ($search) {
                $q->where('mutation_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('creator', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('items', fn ($iq) => $iq->where('medicine_name', 'like', "%{$search}%"));
            });
        }

        $startDate = $request->input('start_date', $request->input('startDate'));
        if ($startDate) {
            $query->whereDate('mutation_date', '>=', $startDate);
        }

        $endDate = $request->input('end_date', $request->input('endDate'));
        if ($endDate) {
            $query->whereDate('mutation_date', '<=', $endDate);
        }

        $sourceWarehouseId = $request->input('source_warehouse_id', $request->input('sourceWarehouseId', $request->input('sourceWarehouse')));
        if ($sourceWarehouseId) {
            if (is_numeric($sourceWarehouseId)) {
                $query->where('source_warehouse_id', (int) $sourceWarehouseId);
            } else {
                $query->whereHas('sourceWarehouse', fn ($q) => $q->where('name', $sourceWarehouseId));
            }
        }

        $targetWarehouseId = $request->input('target_warehouse_id', $request->input('targetWarehouseId', $request->input('targetWarehouse')));
        if ($targetWarehouseId) {
            if (is_numeric($targetWarehouseId)) {
                $query->where('target_warehouse_id', (int) $targetWarehouseId);
            } else {
                $query->whereHas('targetWarehouse', fn ($q) => $q->where('name', $targetWarehouseId));
            }
        }

        $mutations = $query->orderBy('mutation_date', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Daftar_Mutasi_Barang_'.Carbon::now()->format('Y-m-d').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'No',
            'Tanggal',
            'Gudang Asal',
            'Gudang Tujuan',
            'Catatan',
        ];

        return response()->stream(function () use ($mutations, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $months = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
                7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
            ];

            foreach ($mutations as $index => $m) {
                $date = $m->mutation_date ? Carbon::parse($m->mutation_date) : null;
                $dateDisplay = $date
                    ? $date->format('d').' '.$months[(int) $date->format('n')].' '.$date->format('Y, H:i').' WIB'
                    : '-';

                fputcsv($file, [
                    $index + 1,
                    $dateDisplay,
                    $m->sourceWarehouse?->name ?? '-',
                    $m->targetWarehouse?->name ?? '-',
                    $m->notes ?? '-',
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    /**
     * Retrieve available stocks for a specific warehouse.
     */
    public function warehouseStocks(Request $request): JsonResponse
    {
        if (! $request->user()) {
            abort(401, 'Unauthenticated.');
        }

        if (! Gate::allows('viewAny', StockMutation::class) && ! Gate::allows('viewAny', PosSale::class) && ! Gate::allows('viewAny', GoodsIssue::class)) {
            abort(403, 'Unauthorized.');
        }

        $rawWarehouse = $request->input('warehouse_id') ?? $request->input('warehouse');
        if (! $rawWarehouse) {
            return response()->json([
                'message' => 'Parameter warehouse_id atau warehouse wajib disertakan.',
                'errors' => ['warehouse_id' => ['Parameter warehouse_id wajib disertakan.']],
            ], 422);
        }

        $warehouseId = is_numeric($rawWarehouse)
            ? (int) $rawWarehouse
            : Warehouse::where('name', $rawWarehouse)->value('id');

        if (! $warehouseId || ! Warehouse::where('id', $warehouseId)->exists()) {
            return response()->json([
                'message' => 'Gudang tidak ditemukan.',
                'errors' => ['warehouse_id' => ['Gudang tidak ditemukan.']],
            ], 422);
        }

        $query = WarehouseStock::with(['medicine.unit', 'warehouse'])
            ->where('warehouse_id', $warehouseId)
            ->available();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                    ->orWhereHas('medicine', function ($mq) use ($search) {
                        $mq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('all') || $request->input('all') == 1) {
            $stocks = $query->orderBy('medicine_id')->get();

            return WarehouseStockResource::collection($stocks)->response();
        }

        $perPage = (int) $request->input('per_page', $request->input('perPage', 10));
        $stocks = $query->orderBy('medicine_id')->paginate($perPage);

        return WarehouseStockResource::collection($stocks)->response();
    }
}
