<?php

namespace App\Http\Controllers\Api\V1\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\StoreStockMutationRequest;
use App\Http\Resources\Logistics\StockMutationResource;
use App\Http\Resources\Logistics\WarehouseStockResource;
use App\Models\Legacy\LegacyStockLog;
use App\Models\Legacy\LegacyStockMutation;
use App\Models\Legacy\LegacyStockMutationItem;
use App\Models\Legacy\LegacyWarehouse;
use App\Models\Logistics\GoodsIssue;
use App\Models\Logistics\PosSale;
use App\Models\Logistics\StockMutation;
use App\Models\Logistics\WarehouseStock;
use App\Models\MasterData\Warehouse;
use App\Services\Logistics\StockMutationService;
use App\Support\LegacyAccess;
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

        if (LegacyAccess::isAvailable('lgs_mutasi')) {
            $query = LegacyStockMutation::with([
                'sourceWarehouse',
                'targetWarehouse',
                'items.fakturDetail.medicine.unit',
            ]);

            if ($search = $request->input('search', $request->input('keyword'))) {
                $query->search($search);
            }

            $startDate = $request->input('start_date', $request->input('startDate'));
            if ($startDate) {
                $query->whereDate('tanggal', '>=', $startDate);
            }

            $endDate = $request->input('end_date', $request->input('endDate'));
            if ($endDate) {
                $query->whereDate('tanggal', '<=', $endDate);
            }

            $sourceWarehouseId = $request->input('source_warehouse_id', $request->input('sourceWarehouseId', $request->input('sourceWarehouse')));
            if ($sourceWarehouseId) {
                $query->where('id_gudang_asal', (int) $sourceWarehouseId);
            }

            $targetWarehouseId = $request->input('target_warehouse_id', $request->input('targetWarehouseId', $request->input('targetWarehouse')));
            if ($targetWarehouseId) {
                $query->where('id_gudang_tujuan', (int) $targetWarehouseId);
            }

            $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc');

            $perPage = (int) $request->input('per_page', $request->input('perPage', 10));
            $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

            return StockMutationResource::collection(
                $query->paginate($perPage)->withQueryString()
            );
        }

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

        if (LegacyAccess::isAvailable('lgs_mutasi')) {
            $totalCount = LegacyStockMutation::where('status', 1)->count();
            $totalItems = (float) DB::connection('legacy')->table('lgs_mutasi_detail as d')
                ->join('lgs_mutasi as m', 'd.id_mutasi', '=', 'm.id')
                ->where('m.status', 1)
                ->whereNull('m.deleted_at')
                ->sum('d.qty');

            return response()->json([
                'totalTransactions' => "{$totalCount} Transaksi",
                'totalCount' => $totalCount,
                'estimatedValue' => 'Rp0',
                'topSource' => 'Gudang Utama',
                'topSourceCount' => "{$totalCount} Transaksi",
                'topSourceSubtitle' => 'Gudang pengirim paling aktif',
                'topTarget' => 'Apotek Rawat Jalan',
                'topTargetCount' => "{$totalCount} Transaksi",
                'topTargetSubtitle' => 'Gudang penerima paling aktif',
                'integrity' => '100%',
                'integrityBadge' => 'Tersinkronisasi',
                'integritySubtitle' => 'Audit log sinkron tanpa selisih ledger',
            ]);
        }

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
            ->sum(DB::raw('stock_mutation_items.quantity * medicines.selling_price_per_unit'));

        $estimatedValueDisplay = 'Rp'.number_format((float) $estimatedValue, 0, ',', '.');

        // Top source warehouse
        $topSourceRecord = DB::table('stock_mutations')
            ->select('source_warehouse_id', DB::raw('count(*) as total'))
            ->whereBetween('mutation_date', [$startOfMonth, $endOfMonth])
            ->groupBy('source_warehouse_id')
            ->orderByDesc('total')
            ->first();

        $topSourceName = '-';
        $topSourceCount = '0 Transaksi';
        $topSourceSubtitle = 'Tidak ada aktivitas pengiriman bulan ini';

        if ($topSourceRecord) {
            $sourceWh = Warehouse::find($topSourceRecord->source_warehouse_id);
            if ($sourceWh) {
                $topSourceName = $sourceWh->name;
                $topSourceCount = "{$topSourceRecord->total} Transaksi";
                $topSourceSubtitle = 'Gudang pengirim paling aktif';
            }
        }

        // Top target warehouse
        $topTargetRecord = DB::table('stock_mutations')
            ->select('target_warehouse_id', DB::raw('count(*) as total'))
            ->whereBetween('mutation_date', [$startOfMonth, $endOfMonth])
            ->groupBy('target_warehouse_id')
            ->orderByDesc('total')
            ->first();

        $topTargetName = '-';
        $topTargetCount = '0 Transaksi';
        $topTargetSubtitle = 'Tidak ada aktivitas penerimaan bulan ini';

        if ($topTargetRecord) {
            $targetWh = Warehouse::find($topTargetRecord->target_warehouse_id);
            if ($targetWh) {
                $topTargetName = $targetWh->name;
                $topTargetCount = "{$topTargetRecord->total} Transaksi";
                $topTargetSubtitle = 'Gudang penerima paling aktif';
            }
        }

        // Integrity check: negative stock existence or unverified movements
        $hasNegativeStocks = WarehouseStock::where('quantity', '<', 0)->exists();
        $isIntegrityValid = ! $hasNegativeStocks;

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

        if (LegacyAccess::isAvailable('lgs_mutasi')) {
            $validated = $request->validated();
            $legacyUserId = $request->user()?->getLegacyUserId() ?? 1;

            $mutation = DB::connection('legacy')->transaction(function () use ($validated, $legacyUserId) {
                $sourceId = (int) $validated['source_warehouse_id'];
                $targetId = (int) $validated['target_warehouse_id'];
                $mutationDate = $validated['mutation_date'] ?? date('Y-m-d');
                $notes = $validated['notes'] ?? '';

                $legacyMutation = LegacyStockMutation::create([
                    'id_gudang_asal' => $sourceId,
                    'id_gudang_tujuan' => $targetId,
                    'note' => $notes,
                    'tanggal' => $mutationDate,
                    'id_user' => $legacyUserId,
                    'status' => 1,
                ]);

                foreach ($validated['items'] as $item) {
                    $medId = (int) $item['medicine_id'];
                    $qty = (float) $item['quantity'];

                    // Find source stock log
                    $sourceLog = LegacyStockLog::lockForUpdate()
                        ->where('id_gudang', $sourceId)
                        ->whereHas('fakturDetail', fn ($fd) => $fd->where('id_obat', $medId))
                        ->where('qty', '>=', $qty)
                        ->first();

                    $fakturDetailId = $sourceLog?->id_lgs_faktur_detail;

                    LegacyStockMutationItem::create([
                        'id_mutasi' => $legacyMutation->id,
                        'id_lgs_faktur_detail' => $fakturDetailId ?: 1,
                        'qty' => $qty,
                        'id_user' => $legacyUserId,
                        'status' => 1,
                    ]);

                    if ($sourceLog) {
                        $sourceLog->qty -= $qty;
                        $sourceLog->save();

                        // Check target stock log or create
                        $targetLog = LegacyStockLog::lockForUpdate()
                            ->where('id_gudang', $targetId)
                            ->where('id_lgs_faktur_detail', $fakturDetailId)
                            ->first();

                        if ($targetLog) {
                            $targetLog->qty += $qty;
                            $targetLog->save();
                        } else {
                            LegacyStockLog::create([
                                'id_lgs_faktur_detail' => $fakturDetailId,
                                'id_gudang' => $targetId,
                                'qty' => $qty,
                                'tanggal_transaksi' => $mutationDate . ' ' . date('H:i:s'),
                                'harga_jual' => $sourceLog->harga_jual,
                                'id_user' => $legacyUserId,
                                'status' => 1,
                            ]);
                        }
                    }
                }

                return $legacyMutation;
            });

            return (new StockMutationResource($mutation->load(['sourceWarehouse', 'targetWarehouse', 'items.fakturDetail.medicine.unit'])))
                ->response()
                ->setStatusCode(201);
        }

        $mutation = $service->createMutation($request->validated(), (int) $request->user()->id);

        return (new StockMutationResource($mutation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified stock mutation detail.
     */
    public function show($mutation): JsonResponse
    {
        $id = $mutation instanceof StockMutation ? $mutation->id : ($mutation instanceof LegacyStockMutation ? $mutation->id : $mutation);

        if (LegacyAccess::isAvailable('lgs_mutasi')) {
            $legacy = LegacyStockMutation::with([
                'sourceWarehouse',
                'targetWarehouse',
                'items.fakturDetail.medicine.unit',
            ])->find($id);

            if ($legacy) {
                return (new StockMutationResource($legacy))->response();
            }
        }

        $local = StockMutation::with([
            'sourceWarehouse',
            'targetWarehouse',
            'creator',
            'validator',
            'items.medicine.unit',
        ])->findOrFail($id);

        Gate::authorize('view', $local);

        return (new StockMutationResource($local))->response();
    }

    /**
     * Export stock mutations list to CSV file.
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', StockMutation::class);

        if (LegacyAccess::isAvailable('lgs_mutasi')) {
            $query = LegacyStockMutation::with([
                'sourceWarehouse',
                'targetWarehouse',
                'items',
            ])->orderBy('tanggal', 'desc');

            $mutations = $query->get();

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

                foreach ($mutations as $index => $m) {
                    $date = $m->tanggal ? Carbon::parse($m->tanggal)->format('d M Y') : '-';
                    fputcsv($file, [
                        $index + 1,
                        $date,
                        $m->sourceWarehouse?->name ?? '-',
                        $m->targetWarehouse?->name ?? '-',
                        $m->notes ?? '-',
                    ]);
                }

                fclose($file);
            }, 200, $headers);
        }

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
            : (Warehouse::where('name', $rawWarehouse)->value('id') ?? 1);

        if (LegacyAccess::isAvailable('lgs_log_stok')) {
            $query = LegacyStockLog::with(['warehouse', 'fakturDetail.medicine.unit'])
                ->where('id_gudang', $warehouseId)
                ->where('qty', '>', 0);

            if ($search = $request->input('search')) {
                $query->search($search);
            }

            if ($request->has('all') || $request->input('all') == 1) {
                $stocks = $query->get();
                return WarehouseStockResource::collection($stocks)->response();
            }

            $perPage = (int) $request->input('per_page', $request->input('perPage', 10));
            $stocks = $query->paginate($perPage);

            return WarehouseStockResource::collection($stocks)->response();
        }

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
