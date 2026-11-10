<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicationSignaRequest;
use App\Http\Requests\MasterData\UpdateMedicationSignaRequest;
use App\Http\Resources\MasterData\MedicationSignaResource;
use App\Models\Legacy\LegacyMedicationSigna;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MedicationSignaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', LegacyMedicationSigna::class);

        if (! LegacyAccess::isAvailable('ref_signa') && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_signa tidak tersedia.',
            ], 503);
        }

        $perPage = $request->input('per_page', 10);
        $query = LegacyMedicationSigna::query()->orderBy('nama', 'asc');

        if ($search = $request->get('search')) {
            $query->search($search);
        }

        if ($request->has('is_active')) {
            $isActive = $request->boolean('is_active');
            $query->where('status', $isActive ? 1 : 0);
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return MedicationSignaResource::collection($query->get());
        }

        return MedicationSignaResource::collection($query->paginate((int) $perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicationSignaRequest $request)
    {
        Gate::authorize('create', LegacyMedicationSigna::class);

        if (! LegacyAccess::isAvailable('ref_signa') && ! app()->environment('testing')) {
            return response()->json([
                'message' => 'Koneksi ke database operasional SIMRS (simrs_legacy) gagal atau tabel ref_signa tidak tersedia.',
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
            $signa = new LegacyMedicationSigna();
            $signa->name = $data['name'];
            if (isset($data['is_active'])) {
                $signa->is_active = $data['is_active'];
            } else {
                $signa->is_active = true;
            }
            if ($legacyUserId) {
                $signa->id_user = $legacyUserId;
            }
            $signa->save();

            return (new MedicationSignaResource($signa))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show($medicationSigna)
    {
        $id = $medicationSigna instanceof \Illuminate\Database\Eloquent\Model ? $medicationSigna->id : $medicationSigna;
        $legacy = LegacyMedicationSigna::findOrFail($id);
        Gate::authorize('view', $legacy);

        return new MedicationSignaResource($legacy);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicationSignaRequest $request, $medicationSigna)
    {
        $id = $medicationSigna instanceof \Illuminate\Database\Eloquent\Model ? $medicationSigna->id : $medicationSigna;
        $legacy = LegacyMedicationSigna::findOrFail($id);
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

            return new MedicationSignaResource($legacy);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($medicationSigna)
    {
        $id = $medicationSigna instanceof \Illuminate\Database\Eloquent\Model ? $medicationSigna->id : $medicationSigna;
        $legacy = LegacyMedicationSigna::findOrFail($id);
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
                'message' => 'Signa obat tidak dapat dihapus karena masih digunakan pada data resep obat.',
            ], 409);
        }

        DB::connection('legacy')->transaction(function () use ($legacy, $legacyUserId) {
            $legacy->performSoftDelete($legacyUserId);
        });

        return response()->noContent();
    }

    /**
     * Update the active status of the specified resource.
     */
    public function updateStatus(Request $request, $medicationSigna)
    {
        $id = $medicationSigna instanceof \Illuminate\Database\Eloquent\Model ? $medicationSigna->id : $medicationSigna;
        $legacy = LegacyMedicationSigna::findOrFail($id);
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

        return new MedicationSignaResource($legacy);
    }
}
