<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicineUnitRequest;
use App\Http\Requests\MasterData\UpdateMedicineUnitRequest;
use App\Http\Resources\MasterData\MedicineUnitResource;
use App\Models\Legacy\LegacyMedicineUnit;
use App\Models\MasterData\MedicineUnit;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MedicineUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $authModel = LegacyAccess::isAvailable('ref_satuan_obat') ? LegacyMedicineUnit::class : MedicineUnit::class;
        Gate::authorize('viewAny', $authModel);

        $perPage = $request->get('per_page', 10);

        if (LegacyAccess::isAvailable('ref_satuan_obat')) {
            $query = LegacyMedicineUnit::query();

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? '1' : '0');
            }

            $sortBy = $request->get('sort', $request->get('sortBy', 'name'));
            if ($request->has('direction')) {
                $sortDesc = strtolower($request->get('direction')) === 'desc';
            } else {
                $sortDesc = filter_var($request->get('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
            }

            $columnMap = [
                'id' => 'id',
                'name' => 'nama',
                'description' => 'deskripsi',
                'is_active' => 'status',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ];
            $sortCol = $columnMap[$sortBy] ?? 'nama';
            $query->orderBy($sortCol, $sortDesc ? 'desc' : 'asc');

            if ($perPage === '-1' || $perPage === 'all') {
                return MedicineUnitResource::collection($query->get());
            }

            return MedicineUnitResource::collection($query->paginate((int) $perPage));
        }

        $query = MedicineUnit::query();

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->get('sort', $request->get('sortBy', 'created_at'));
        if ($request->has('direction')) {
            $sortDesc = strtolower($request->get('direction')) === 'desc';
        } else {
            $sortDesc = filter_var($request->get('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        }

        $allowedSorts = ['id', 'name', 'description', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');

        if ($perPage === '-1' || $perPage === 'all') {
            return MedicineUnitResource::collection($query->get());
        }

        return MedicineUnitResource::collection($query->paginate((int) $perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicineUnitRequest $request)
    {
        $authModel = LegacyAccess::isAvailable('ref_satuan_obat') ? LegacyMedicineUnit::class : MedicineUnit::class;
        Gate::authorize('create', $authModel);

        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_satuan_obat')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            if (LegacyMedicineUnit::where('nama', $data['name'])->exists()) {
                throw ValidationException::withMessages([
                    'name' => ['Nama satuan obat sudah terdaftar.'],
                ]);
            }

            return DB::connection('legacy')->transaction(function () use ($data, $legacyUserId) {
                $unit = new LegacyMedicineUnit();
                $unit->name = $data['name'];
                if (array_key_exists('description', $data)) {
                    $unit->description = $data['description'];
                }
                if (isset($data['is_active'])) {
                    $unit->is_active = $data['is_active'];
                }
                if ($legacyUserId) {
                    $unit->id_user = $legacyUserId;
                }
                $unit->save();

                return (new MedicineUnitResource($unit))->response()->setStatusCode(201);
            });
        }

        $medicineUnit = MedicineUnit::create($data);

        return (new MedicineUnitResource($medicineUnit))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show($medicineUnit)
    {
        $id = $medicineUnit instanceof \Illuminate\Database\Eloquent\Model ? $medicineUnit->id : $medicineUnit;

        if (LegacyAccess::isAvailable('ref_satuan_obat')) {
            $legacy = LegacyMedicineUnit::findOrFail($id);
            Gate::authorize('view', $legacy);

            return new MedicineUnitResource($legacy);
        }

        if (! ($medicineUnit instanceof MedicineUnit)) {
            $medicineUnit = MedicineUnit::findOrFail($id);
        }

        Gate::authorize('view', $medicineUnit);

        return new MedicineUnitResource($medicineUnit);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicineUnitRequest $request, $medicineUnit)
    {
        $id = $medicineUnit instanceof \Illuminate\Database\Eloquent\Model ? $medicineUnit->id : $medicineUnit;

        if (LegacyAccess::isAvailable('ref_satuan_obat')) {
            $legacy = LegacyMedicineUnit::findOrFail($id);
            Gate::authorize('update', $legacy);

            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            $data = $request->validated();

            if ($data['name'] !== $legacy->name) {
                if (LegacyMedicineUnit::where('nama', $data['name'])->where('id', '!=', $id)->exists()) {
                    throw ValidationException::withMessages([
                        'name' => ['Nama satuan obat sudah terdaftar.'],
                    ]);
                }
            }

            return DB::connection('legacy')->transaction(function () use ($legacy, $data, $legacyUserId) {
                $legacy->name = $data['name'];
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

                return new MedicineUnitResource($legacy);
            });
        }

        if (! ($medicineUnit instanceof MedicineUnit)) {
            $medicineUnit = MedicineUnit::findOrFail($id);
        }

        Gate::authorize('update', $medicineUnit);

        $medicineUnit->update($request->validated());

        return new MedicineUnitResource($medicineUnit);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($medicineUnit)
    {
        $id = $medicineUnit instanceof \Illuminate\Database\Eloquent\Model ? $medicineUnit->id : $medicineUnit;

        if (LegacyAccess::isAvailable('ref_satuan_obat')) {
            $legacy = LegacyMedicineUnit::findOrFail($id);
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
                    'message' => 'Satuan obat tidak dapat dihapus karena masih digunakan pada data obat atau riwayat resep.',
                ], 409);
            }

            DB::connection('legacy')->transaction(function () use ($legacy, $legacyUserId) {
                $legacy->performSoftDelete($legacyUserId);
            });

            return response()->noContent();
        }

        if (! ($medicineUnit instanceof MedicineUnit)) {
            $medicineUnit = MedicineUnit::findOrFail($id);
        }

        Gate::authorize('delete', $medicineUnit);

        $medicineUnit->delete();

        return response()->noContent();
    }

    /**
     * Update the active status of the specified resource.
     */
    public function updateStatus(Request $request, $medicineUnit)
    {
        $id = $medicineUnit instanceof \Illuminate\Database\Eloquent\Model ? $medicineUnit->id : $medicineUnit;

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        if (LegacyAccess::isAvailable('ref_satuan_obat')) {
            $legacy = LegacyMedicineUnit::findOrFail($id);
            Gate::authorize('update', $legacy);

            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId();

            if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
                return response()->json([
                    'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
                ], 403);
            }

            DB::connection('legacy')->transaction(function () use ($legacy, $validated, $legacyUserId) {
                $legacy->is_active = $validated['is_active'];
                if ($legacyUserId) {
                    $legacy->id_user = $legacyUserId;
                }
                $legacy->save();
            });

            return new MedicineUnitResource($legacy);
        }

        if (! ($medicineUnit instanceof MedicineUnit)) {
            $medicineUnit = MedicineUnit::findOrFail($id);
        }

        Gate::authorize('update', $medicineUnit);

        $medicineUnit->update($validated);

        return new MedicineUnitResource($medicineUnit);
    }
}
