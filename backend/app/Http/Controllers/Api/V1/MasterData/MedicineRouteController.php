<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicineRouteRequest;
use App\Http\Requests\MasterData\UpdateMedicineRouteRequest;
use App\Http\Resources\MasterData\MedicineRouteResource;
use App\Models\Legacy\LegacyMedicineRoute;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MedicineRouteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', LegacyMedicineRoute::class);

        if (! LegacyAccess::isAvailable('ref_obat_jalur_masuk') && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_obat_jalur_masuk tidak tersedia.',
            ], 503);
        }

        $perPage = $request->input('per_page', 10);
        $query = LegacyMedicineRoute::query()->orderBy('rute_obat', 'asc');

        if ($search = $request->get('search')) {
            $query->search($search);
        }

        if ($request->has('is_active')) {
            $isActive = $request->boolean('is_active');
            $query->where('status', $isActive ? 1 : 0);
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return MedicineRouteResource::collection($query->get());
        }

        return MedicineRouteResource::collection($query->paginate((int) $perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicineRouteRequest $request)
    {
        Gate::authorize('create', LegacyMedicineRoute::class);

        if (! LegacyAccess::isAvailable('ref_obat_jalur_masuk') && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_obat_jalur_masuk tidak tersedia.',
            ], 503);
        }

        $user = $request->user();
        $legacyUserId = $user?->getLegacyUserId();

        if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
            return response()->json([
                'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
            ], 403);
        }

        $data = $request->validated();

        return DB::connection('legacy')->transaction(function () use ($data, $legacyUserId) {
            $route = new LegacyMedicineRoute();
            $route->name = $data['name'];
            if (array_key_exists('object_code', $data)) {
                $route->object_code = $data['object_code'];
            }
            if (isset($data['is_active'])) {
                $route->is_active = $data['is_active'];
            } else {
                $route->is_active = true;
            }
            if ($legacyUserId) {
                $route->user_id = $legacyUserId;
            }
            $route->save();

            return (new MedicineRouteResource($route))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show($medicineRoute)
    {
        $id = $medicineRoute instanceof \Illuminate\Database\Eloquent\Model ? $medicineRoute->id : $medicineRoute;
        $legacy = LegacyMedicineRoute::findOrFail($id);
        Gate::authorize('view', $legacy);

        return new MedicineRouteResource($legacy);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicineRouteRequest $request, $medicineRoute)
    {
        $id = $medicineRoute instanceof \Illuminate\Database\Eloquent\Model ? $medicineRoute->id : $medicineRoute;
        $legacy = LegacyMedicineRoute::findOrFail($id);
        Gate::authorize('update', $legacy);

        $user = $request->user();
        $legacyUserId = $user?->getLegacyUserId();

        if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
            return response()->json([
                'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
            ], 403);
        }

        $data = $request->validated();

        return DB::connection('legacy')->transaction(function () use ($legacy, $data, $legacyUserId) {
            $legacy->name = $data['name'];
            if (array_key_exists('object_code', $data)) {
                $legacy->object_code = $data['object_code'];
            }
            if (isset($data['is_active'])) {
                $legacy->is_active = $data['is_active'];
            }
            if ($legacyUserId) {
                $legacy->user_id = $legacyUserId;
            }
            $legacy->save();

            return new MedicineRouteResource($legacy);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($medicineRoute)
    {
        $id = $medicineRoute instanceof \Illuminate\Database\Eloquent\Model ? $medicineRoute->id : $medicineRoute;
        $legacy = LegacyMedicineRoute::findOrFail($id);
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
                'message' => 'Jalur masuk obat tidak dapat dihapus karena masih digunakan pada data obat.',
            ], 409);
        }

        DB::connection('legacy')->transaction(function () use ($legacy, $legacyUserId) {
            $legacy->performSoftDelete($legacyUserId);
        });

        return response()->noContent();
    }

    /**
     * Update status.
     */
    public function updateStatus(Request $request, $medicineRoute)
    {
        $id = $medicineRoute instanceof \Illuminate\Database\Eloquent\Model ? $medicineRoute->id : $medicineRoute;
        $legacy = LegacyMedicineRoute::findOrFail($id);
        Gate::authorize('update', $legacy);

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

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
                $legacy->user_id = $legacyUserId;
            }
            $legacy->save();
        });

        return new MedicineRouteResource($legacy);
    }
}
