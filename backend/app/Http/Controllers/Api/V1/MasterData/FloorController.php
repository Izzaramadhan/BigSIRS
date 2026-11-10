<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreFloorRequest;
use App\Http\Requests\MasterData\UpdateFloorRequest;
use App\Http\Resources\MasterData\FloorResource;
use App\Models\Legacy\LegacyFloor;
use App\Models\MasterData\Floor;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FloorController extends Controller
{
    /**
     * Display a listing of the floors.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        if (LegacyAccess::isAvailable('ref_lantai')) {
            $query = LegacyFloor::with('building')->orderBy('nama', 'asc');

            if ($request->filled('building_id')) {
                $query->where('id_gedung', $request->building_id);
            }

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($perPage === '-1' || $perPage === 'all') {
                return FloorResource::collection($query->get());
            }

            $perPageInt = min(max((int) $perPage, 1), 100);
            return FloorResource::collection($query->paginate($perPageInt));
        }

        $sort = $request->query('sort', $request->query('sortBy', 'created_at'));

        if ($request->has('direction')) {
            $direction = strtolower($request->query('direction')) === 'desc' ? 'desc' : 'asc';
        } else {
            $direction = filter_var($request->query('sortDesc', false), FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
        }

        $allowedSorts = ['id', 'name', 'building_id', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
            $direction = 'desc';
        }

        $query = Floor::with('building');

        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return FloorResource::collection($query->orderBy($sort, $direction)->get());
        }

        $perPageInt = min(max((int) $perPage, 1), 100);
        $floors = $query->orderBy($sort, $direction)->paginate($perPageInt);

        return FloorResource::collection($floors);
    }

    /**
     * Store a newly created floor in storage.
     */
    public function store(StoreFloorRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_lantai')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            return DB::connection('legacy')->transaction(function () use ($request, $legacyUserId) {
                $data = $request->validated();
                $floor = LegacyFloor::create([
                    'id_gedung' => $data['building_id'],
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'status' => '1',
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return (new FloorResource($floor->load('building')))->response()->setStatusCode(201);
            });
        }

        return DB::transaction(function () use ($request) {
            $floor = Floor::create($request->validated());
            $floor->load('building');

            return (new FloorResource($floor))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified floor.
     */
    public function show($floor)
    {
        $id = $floor instanceof Floor ? $floor->id : $floor;
        if (LegacyAccess::isAvailable('ref_lantai')) {
            $legacy = LegacyFloor::with('building')->findOrFail($id);
            return new FloorResource($legacy);
        }

        if (! ($floor instanceof Floor)) {
            $floor = Floor::findOrFail($id);
        }

        $floor->load('building');

        return new FloorResource($floor);
    }

    /**
     * Update the specified floor in storage.
     */
    public function update(UpdateFloorRequest $request, $floor)
    {
        $id = $floor instanceof Floor ? $floor->id : $floor;

        if (LegacyAccess::isAvailable('ref_lantai')) {
            return DB::connection('legacy')->transaction(function () use ($request, $id) {
                $legacy = LegacyFloor::findOrFail($id);
                $data = $request->validated();
                $legacy->update([
                    'id_gedung' => $data['building_id'],
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'updated_at' => now(),
                ]);

                return new FloorResource($legacy->load('building'));
            });
        }

        if (! ($floor instanceof Floor)) {
            $floor = Floor::findOrFail($id);
        }

        return DB::transaction(function () use ($request, $floor) {
            $floor->update($request->validated());
            $floor->load('building');

            return new FloorResource($floor);
        });
    }

    /**
     * Remove the specified floor from storage.
     */
    public function destroy($floor)
    {
        $id = $floor instanceof Floor ? $floor->id : $floor;

        if (LegacyAccess::isAvailable('ref_lantai')) {
            $legacy = LegacyFloor::findOrFail($id);
            if ($legacy->wards()->exists()) {
                return response()->json([
                    'message' => 'Lantai tidak dapat dihapus karena masih digunakan oleh data bangsal.',
                ], 422);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($floor instanceof Floor)) {
            $floor = Floor::findOrFail($id);
        }

        if ($floor->wards()->exists()) {
            return response()->json([
                'message' => 'Lantai tidak dapat dihapus karena masih digunakan oleh data bangsal.',
            ], 422);
        }

        DB::transaction(function () use ($floor) {
            $floor->delete();
        });

        return response()->noContent();
    }
}
