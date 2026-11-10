<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreWarehouseRequest;
use App\Http\Requests\MasterData\UpdateWarehouseRequest;
use App\Http\Resources\MasterData\WarehouseResource;
use App\Models\Legacy\LegacyWarehouse;
use App\Models\MasterData\Warehouse;
use App\Models\Polyclinic;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $authModel = LegacyAccess::isAvailable('ref_gudang') ? LegacyWarehouse::class : Warehouse::class;
        Gate::authorize('viewAny', $authModel);

        $perPage = $request->input('per_page', 10);

        if (LegacyAccess::isAvailable('ref_gudang')) {
            $query = LegacyWarehouse::query();

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? '1' : '0');
            }

            $sortBy = $request->input('sort', $request->input('sortBy', 'name'));
            if ($request->has('direction')) {
                $sortDesc = strtolower($request->input('direction')) === 'desc';
            } else {
                $sortDesc = filter_var($request->input('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
            }

            $columnMap = [
                'id' => 'id',
                'code' => 'kode',
                'name' => 'nama',
                'description' => 'deskripsi',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ];
            $sortCol = $columnMap[$sortBy] ?? 'nama';
            $query->orderBy($sortCol, $sortDesc ? 'desc' : 'asc');

            if ($perPage === '-1' || $perPage === 'all') {
                return WarehouseResource::collection($query->get());
            }

            return WarehouseResource::collection($query->paginate((int) $perPage));
        }

        $query = Warehouse::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->input('sort', $request->input('sortBy', 'created_at'));
        if ($request->has('direction')) {
            $sortDesc = strtolower($request->input('direction')) === 'desc';
        } else {
            $sortDesc = filter_var($request->input('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        }

        $allowedSorts = ['id', 'code', 'name', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return WarehouseResource::collection($query->get());
        }

        $warehouses = $query->paginate((int) $perPage);

        return WarehouseResource::collection($warehouses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWarehouseRequest $request)
    {
        $authModel = LegacyAccess::isAvailable('ref_gudang') ? LegacyWarehouse::class : Warehouse::class;
        Gate::authorize('create', $authModel);

        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_gudang')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            if (LegacyWarehouse::where('nama', $data['name'])->exists()) {
                throw ValidationException::withMessages([
                    'name' => ['Nama gudang sudah terdaftar.'],
                ]);
            }

            if (! empty($data['code']) && $data['code'] !== '-') {
                if (LegacyWarehouse::where('kode', $data['code'])->exists()) {
                    throw ValidationException::withMessages([
                        'code' => ['Kode gudang sudah terdaftar.'],
                    ]);
                }
            }

            return DB::connection('legacy')->transaction(function () use ($data, $legacyUserId) {
                $warehouse = new LegacyWarehouse();
                $warehouse->name = $data['name'];
                if (array_key_exists('code', $data)) {
                    $warehouse->code = $data['code'];
                }
                if (array_key_exists('description', $data)) {
                    $warehouse->description = $data['description'];
                }
                if (isset($data['is_active'])) {
                    $warehouse->is_active = $data['is_active'];
                }
                if ($legacyUserId) {
                    $warehouse->id_user = $legacyUserId;
                }
                $warehouse->save();

                return (new WarehouseResource($warehouse))->response()->setStatusCode(201);
            });
        }

        $warehouse = Warehouse::create($data);

        return (new WarehouseResource($warehouse))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show($warehouse)
    {
        $id = $warehouse instanceof \Illuminate\Database\Eloquent\Model ? $warehouse->id : $warehouse;

        if (LegacyAccess::isAvailable('ref_gudang')) {
            $legacy = LegacyWarehouse::findOrFail($id);
            Gate::authorize('view', $legacy);

            return new WarehouseResource($legacy);
        }

        if (! ($warehouse instanceof Warehouse)) {
            $warehouse = Warehouse::findOrFail($id);
        }

        Gate::authorize('view', $warehouse);

        return new WarehouseResource($warehouse);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWarehouseRequest $request, $warehouse)
    {
        $id = $warehouse instanceof \Illuminate\Database\Eloquent\Model ? $warehouse->id : $warehouse;

        if (LegacyAccess::isAvailable('ref_gudang')) {
            $legacy = LegacyWarehouse::findOrFail($id);
            Gate::authorize('update', $legacy);

            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            $data = $request->validated();

            if (LegacyWarehouse::where('nama', $data['name'])->where('id', '!=', $id)->exists()) {
                throw ValidationException::withMessages([
                    'name' => ['Nama gudang sudah terdaftar.'],
                ]);
            }

            if (! empty($data['code']) && $data['code'] !== '-') {
                if (LegacyWarehouse::where('kode', $data['code'])->where('id', '!=', $id)->exists()) {
                    throw ValidationException::withMessages([
                        'code' => ['Kode gudang sudah terdaftar.'],
                    ]);
                }
            }

            return DB::connection('legacy')->transaction(function () use ($legacy, $data, $legacyUserId) {
                $legacy->name = $data['name'];
                if (array_key_exists('code', $data)) {
                    $legacy->code = $data['code'];
                }
                if (array_key_exists('description', $data)) {
                    $legacy->description = $data['description'];
                }
                if (isset($data['is_active'])) {
                    $legacy->is_active = $data['is_active'];
                }
                if ($legacyUserId) {
                    $legacy->id_user = $legacyUserId;
                }
                $legacy->save();

                return new WarehouseResource($legacy);
            });
        }

        if (! ($warehouse instanceof Warehouse)) {
            $warehouse = Warehouse::findOrFail($id);
        }

        Gate::authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return new WarehouseResource($warehouse);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($warehouse)
    {
        $id = $warehouse instanceof \Illuminate\Database\Eloquent\Model ? $warehouse->id : $warehouse;

        if (LegacyAccess::isAvailable('ref_gudang')) {
            $legacy = LegacyWarehouse::findOrFail($id);
            Gate::authorize('delete', $legacy);

            $user = request()->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            if ($legacy->isReferencedInTransactions()) {
                return response()->json([
                    'message' => 'Gudang tidak dapat dihapus karena masih digunakan sebagai gudang default pada poliklinik atau memiliki riwayat logistik.',
                ], 409);
            }

            DB::connection('legacy')->transaction(function () use ($legacy, $legacyUserId) {
                $legacy->performSoftDelete($legacyUserId);
            });

            return response()->noContent();
        }

        if (! ($warehouse instanceof Warehouse)) {
            $warehouse = Warehouse::findOrFail($id);
        }

        Gate::authorize('delete', $warehouse);

        if ($warehouse->legacy_id) {
            $isUsedInPolyclinic = Polyclinic::where('legacy_default_warehouse_id', $warehouse->legacy_id)->exists();
            if ($isUsedInPolyclinic) {
                return response()->json([
                    'message' => 'Gudang tidak dapat dihapus karena masih digunakan sebagai gudang default pada poliklinik.',
                ], 409);
            }
        }

        $isUsedInPolyclinicDirectly = Polyclinic::where('warehouse_id', $warehouse->id)->exists();
        if ($isUsedInPolyclinicDirectly) {
            return response()->json([
                'message' => 'Gudang tidak dapat dihapus karena masih digunakan sebagai gudang default pada poliklinik.',
            ], 409);
        }

        $warehouse->delete();

        return response()->noContent();
    }
}
