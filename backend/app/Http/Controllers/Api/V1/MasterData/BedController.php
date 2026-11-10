<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreBedRequest;
use App\Http\Requests\MasterData\UpdateBedRequest;
use App\Http\Resources\MasterData\BedResource;
use App\Models\Legacy\LegacyBed;
use App\Models\MasterData\Bed;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BedController extends Controller
{
    /**
     * Display a listing of the beds.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        if (LegacyAccess::isAvailable('ref_bed')) {
            $query = LegacyBed::with(['room.ward.floor.building', 'room.roomClass'])->orderBy('nama', 'asc');

            if ($request->filled('room_id')) {
                $query->where('id_kamar', $request->room_id);
            }

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($perPage === '-1' || $perPage === 'all') {
                return BedResource::collection($query->get());
            }

            $perPageInt = min(max((int) $perPage, 1), 100);
            return BedResource::collection($query->paginate($perPageInt));
        }

        $sort = $request->query('sort', $request->query('sortBy', 'created_at'));

        if ($request->has('direction')) {
            $direction = strtolower($request->query('direction')) === 'desc' ? 'desc' : 'asc';
        } else {
            $direction = filter_var($request->query('sortDesc', false), FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
        }

        $allowedSorts = ['id', 'name', 'room_id', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
            $direction = 'desc';
        }

        $query = Bed::with(['room.ward.floor.building', 'room.roomClass']);

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return BedResource::collection($query->orderBy($sort, $direction)->get());
        }

        $perPageInt = min(max((int) $perPage, 1), 100);
        $beds = $query->orderBy($sort, $direction)->paginate($perPageInt);

        return BedResource::collection($beds);
    }

    /**
     * Store a newly created bed in storage.
     */
    public function store(StoreBedRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_bed')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            return DB::connection('legacy')->transaction(function () use ($request, $legacyUserId) {
                $data = $request->validated();
                $bed = LegacyBed::create([
                    'id_kamar' => $data['room_id'],
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'tersedia' => 'kosong',
                    'status' => '1',
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return (new BedResource($bed->load(['room.ward.floor.building', 'room.roomClass'])))->response()->setStatusCode(201);
            });
        }

        return DB::transaction(function () use ($request) {
            $bed = Bed::create($request->validated());
            $bed->load(['room.ward.floor.building', 'room.roomClass']);

            return (new BedResource($bed))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified bed.
     */
    public function show($bed)
    {
        $id = $bed instanceof Bed ? $bed->id : $bed;
        if (LegacyAccess::isAvailable('ref_bed')) {
            $legacy = LegacyBed::with(['room.ward.floor.building', 'room.roomClass'])->findOrFail($id);
            return new BedResource($legacy);
        }

        if (! ($bed instanceof Bed)) {
            $bed = Bed::findOrFail($id);
        }

        $bed->load(['room.ward.floor.building', 'room.roomClass']);

        return new BedResource($bed);
    }

    /**
     * Update the specified bed in storage.
     */
    public function update(UpdateBedRequest $request, $bed)
    {
        $id = $bed instanceof Bed ? $bed->id : $bed;

        if (LegacyAccess::isAvailable('ref_bed')) {
            return DB::connection('legacy')->transaction(function () use ($request, $id) {
                $legacy = LegacyBed::findOrFail($id);
                $data = $request->validated();
                $legacy->update([
                    'id_kamar' => $data['room_id'],
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'updated_at' => now(),
                ]);

                return new BedResource($legacy->load(['room.ward.floor.building', 'room.roomClass']));
            });
        }

        if (! ($bed instanceof Bed)) {
            $bed = Bed::findOrFail($id);
        }

        return DB::transaction(function () use ($request, $bed) {
            $bed->update($request->validated());
            $bed->load(['room.ward.floor.building', 'room.roomClass']);

            return new BedResource($bed);
        });
    }

    /**
     * Remove the specified bed from storage.
     */
    public function destroy($bed)
    {
        $id = $bed instanceof Bed ? $bed->id : $bed;

        if (LegacyAccess::isAvailable('ref_bed')) {
            $legacy = LegacyBed::findOrFail($id);

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($bed instanceof Bed)) {
            $bed = Bed::findOrFail($id);
        }

        if ($bed->isUsedInTransactions()) {
            return response()->json([
                'message' => 'Bed tidak dapat dihapus karena masih digunakan pada data admisi atau transaksi pasien.',
            ], 422);
        }

        DB::transaction(function () use ($bed) {
            $bed->delete();
        });

        return response()->noContent();
    }
}
