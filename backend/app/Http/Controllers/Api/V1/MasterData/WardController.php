<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreWardRequest;
use App\Http\Requests\MasterData\UpdateWardRequest;
use App\Http\Resources\MasterData\WardResource;
use App\Models\Legacy\LegacyWard;
use App\Models\MasterData\Ward;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WardController extends Controller
{
    /**
     * Display a listing of the wards.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        if (LegacyAccess::isAvailable('ref_bangsal')) {
            $query = LegacyWard::with(['floor.building'])->orderBy('nama', 'asc');

            if ($request->filled('floor_id')) {
                $query->where('id_lantai', $request->floor_id);
            }

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($perPage === '-1' || $perPage === 'all') {
                return WardResource::collection($query->get());
            }

            $perPageInt = min(max((int) $perPage, 1), 100);
            return WardResource::collection($query->paginate($perPageInt));
        }

        $sort = $request->query('sort', $request->query('sortBy', 'created_at'));

        if ($request->has('direction')) {
            $direction = strtolower($request->query('direction')) === 'desc' ? 'desc' : 'asc';
        } else {
            $direction = filter_var($request->query('sortDesc', false), FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
        }

        $allowedSorts = ['id', 'name', 'floor_id', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
            $direction = 'desc';
        }

        $query = Ward::with(['floor.building']);

        if ($request->filled('floor_id')) {
            $query->where('floor_id', $request->floor_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return WardResource::collection($query->orderBy($sort, $direction)->get());
        }

        $perPageInt = min(max((int) $perPage, 1), 100);
        $wards = $query->orderBy($sort, $direction)->paginate($perPageInt);

        return WardResource::collection($wards);
    }

    /**
     * Store a newly created ward in storage.
     */
    public function store(StoreWardRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_bangsal')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            return DB::connection('legacy')->transaction(function () use ($request, $legacyUserId) {
                $data = $request->validated();
                $ward = LegacyWard::create([
                    'id_lantai' => $data['floor_id'],
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'status' => '1',
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return (new WardResource($ward->load(['floor.building'])))->response()->setStatusCode(201);
            });
        }

        return DB::transaction(function () use ($request) {
            $ward = Ward::create($request->validated());
            $ward->load(['floor.building']);

            return (new WardResource($ward))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified ward.
     */
    public function show($ward)
    {
        $id = $ward instanceof Ward ? $ward->id : $ward;
        if (LegacyAccess::isAvailable('ref_bangsal')) {
            $legacy = LegacyWard::with(['floor.building'])->findOrFail($id);
            return new WardResource($legacy);
        }

        if (! ($ward instanceof Ward)) {
            $ward = Ward::findOrFail($id);
        }

        $ward->load(['floor.building']);

        return new WardResource($ward);
    }

    /**
     * Update the specified ward in storage.
     */
    public function update(UpdateWardRequest $request, $ward)
    {
        $id = $ward instanceof Ward ? $ward->id : $ward;

        if (LegacyAccess::isAvailable('ref_bangsal')) {
            return DB::connection('legacy')->transaction(function () use ($request, $id) {
                $legacy = LegacyWard::findOrFail($id);
                $data = $request->validated();
                $legacy->update([
                    'id_lantai' => $data['floor_id'],
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'updated_at' => now(),
                ]);

                return new WardResource($legacy->load(['floor.building']));
            });
        }

        if (! ($ward instanceof Ward)) {
            $ward = Ward::findOrFail($id);
        }

        return DB::transaction(function () use ($request, $ward) {
            $ward->update($request->validated());
            $ward->load(['floor.building']);

            return new WardResource($ward);
        });
    }

    /**
     * Remove the specified ward from storage.
     */
    public function destroy($ward)
    {
        $id = $ward instanceof Ward ? $ward->id : $ward;

        if (LegacyAccess::isAvailable('ref_bangsal')) {
            $legacy = LegacyWard::findOrFail($id);
            if ($legacy->rooms()->exists()) {
                return response()->json([
                    'message' => 'Bangsal tidak dapat dihapus karena masih digunakan oleh data kamar.',
                ], 422);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($ward instanceof Ward)) {
            $ward = Ward::findOrFail($id);
        }

        if (method_exists($ward, 'rooms') && $ward->rooms()->exists()) {
            return response()->json([
                'message' => 'Bangsal tidak dapat dihapus karena masih digunakan oleh data kamar.',
            ], 422);
        }

        if (method_exists($ward, 'beds') && $ward->beds()->exists()) {
            return response()->json([
                'message' => 'Bangsal tidak dapat dihapus karena masih digunakan oleh data bed/tempat tidur.',
            ], 422);
        }

        DB::transaction(function () use ($ward) {
            $ward->delete();
        });

        return response()->noContent();
    }
}
