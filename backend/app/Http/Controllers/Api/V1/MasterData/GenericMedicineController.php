<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreGenericMedicineRequest;
use App\Http\Requests\MasterData\UpdateGenericMedicineRequest;
use App\Http\Resources\MasterData\GenericMedicineResource;
use App\Models\Legacy\LegacyGenericMedicine;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GenericMedicineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', LegacyGenericMedicine::class);

        if (! LegacyAccess::isAvailable('ref_generik') && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_generik tidak tersedia.',
            ], 503);
        }

        $perPage = $request->get('per_page', 10);
        $query = LegacyGenericMedicine::query()->orderBy('nama_objek', 'asc');

        if ($request->has('search') && ! empty($request->search)) {
            $query->search($request->search);
        }

        if ($request->has('is_active')) {
            $query->where('status', $request->boolean('is_active') ? 1 : 0);
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return GenericMedicineResource::collection($query->get());
        }

        return GenericMedicineResource::collection($query->paginate((int) $perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGenericMedicineRequest $request)
    {
        Gate::authorize('create', LegacyGenericMedicine::class);

        if (! LegacyAccess::isAvailable('ref_generik') && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_generik tidak tersedia.',
            ], 503);
        }

        $user = $request->user();
        $legacyUserId = $user?->getLegacyUserId();

        if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
            return response()->json([
                'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
            ], 403);
        }

        $name = preg_replace('/\s+/', ' ', trim($request->validated('name')));

        // Check race-condition existing
        $existing = LegacyGenericMedicine::whereRaw('LOWER(nama_objek) = ?', [strtolower($name)])->first();
        if ($existing) {
            if (! $existing->is_active) {
                $existing->is_active = true;
                if ($legacyUserId) {
                    $existing->id_user = $legacyUserId;
                }
                $existing->save();
            }

            return (new GenericMedicineResource($existing))
                ->additional(['message' => 'Data generik sudah tersedia.'])
                ->response()
                ->setStatusCode(200);
        }

        return DB::connection('legacy')->transaction(function () use ($name, $request, $legacyUserId) {
            $generic = new LegacyGenericMedicine();
            $generic->name = $name;
            $generic->is_active = $request->has('is_active') ? $request->boolean('is_active') : true;
            if ($legacyUserId) {
                $generic->id_user = $legacyUserId;
            }
            $generic->save();

            return (new GenericMedicineResource($generic))
                ->additional(['message' => 'Data generik berhasil ditambahkan.'])
                ->response()
                ->setStatusCode(201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show($genericMedicine)
    {
        $id = $genericMedicine instanceof \Illuminate\Database\Eloquent\Model ? $genericMedicine->id : $genericMedicine;
        $legacy = LegacyGenericMedicine::findOrFail($id);
        Gate::authorize('view', $legacy);

        return new GenericMedicineResource($legacy);
    }

    /**
     * Update the active status of the specified resource.
     */
    public function updateStatus(Request $request, $genericMedicine)
    {
        $id = $genericMedicine instanceof \Illuminate\Database\Eloquent\Model ? $genericMedicine->id : $genericMedicine;
        $legacy = LegacyGenericMedicine::findOrFail($id);
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
                $legacy->id_user = $legacyUserId;
            }
            $legacy->save();
        });

        return new GenericMedicineResource($legacy);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGenericMedicineRequest $request, $genericMedicine)
    {
        $id = $genericMedicine instanceof \Illuminate\Database\Eloquent\Model ? $genericMedicine->id : $genericMedicine;
        $legacy = LegacyGenericMedicine::findOrFail($id);
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
            if (isset($data['is_active'])) {
                $legacy->is_active = $data['is_active'];
            }
            if ($legacyUserId) {
                $legacy->id_user = $legacyUserId;
            }
            $legacy->save();

            return new GenericMedicineResource($legacy);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($genericMedicine)
    {
        $id = $genericMedicine instanceof \Illuminate\Database\Eloquent\Model ? $genericMedicine->id : $genericMedicine;
        $legacy = LegacyGenericMedicine::findOrFail($id);
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
                'message' => 'Data generik tidak dapat dihapus karena masih digunakan pada data obat.',
            ], 409);
        }

        DB::connection('legacy')->transaction(function () use ($legacy, $legacyUserId) {
            $legacy->performSoftDelete($legacyUserId);
        });

        return response()->noContent();
    }
}
