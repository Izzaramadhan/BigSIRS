<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreBuildingRequest;
use App\Http\Requests\MasterData\UpdateBuildingRequest;
use App\Http\Resources\MasterData\BuildingResource;
use App\Models\Legacy\LegacyBuilding;
use App\Models\MasterData\Building;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuildingController extends Controller
{
    /**
     * Display a listing of the buildings.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        if (LegacyAccess::isAvailable('ref_gedung')) {
            $query = LegacyBuilding::query()->orderBy('nama', 'asc');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($perPage === '-1' || $perPage === 'all') {
                return BuildingResource::collection($query->get());
            }

            $perPageInt = min(max((int) $perPage, 1), 100);
            return BuildingResource::collection($query->paginate($perPageInt));
        }

        $sort = $request->query('sort', $request->query('sortBy', 'created_at'));

        if ($request->has('direction')) {
            $direction = strtolower($request->query('direction')) === 'desc' ? 'desc' : 'asc';
        } else {
            $direction = filter_var($request->query('sortDesc', false), FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
        }

        $allowedSorts = ['id', 'name', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
            $direction = 'desc';
        }

        $query = Building::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return BuildingResource::collection($query->orderBy($sort, $direction)->get());
        }

        $perPageInt = min(max((int) $perPage, 1), 100);
        $buildings = $query->orderBy($sort, $direction)->paginate($perPageInt);

        return BuildingResource::collection($buildings);
    }

    /**
     * Store a newly created building in storage.
     */
    public function store(StoreBuildingRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_gedung')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            return DB::connection('legacy')->transaction(function () use ($request, $legacyUserId) {
                $data = $request->validated();
                $building = LegacyBuilding::create([
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'status' => '1',
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return (new BuildingResource($building))->response()->setStatusCode(201);
            });
        }

        return DB::transaction(function () use ($request) {
            $building = Building::create($request->validated());

            return (new BuildingResource($building))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified building.
     */
    public function show($building)
    {
        $id = $building instanceof Building ? $building->id : $building;
        if (LegacyAccess::isAvailable('ref_gedung')) {
            $legacy = LegacyBuilding::findOrFail($id);
            return new BuildingResource($legacy);
        }

        if (! ($building instanceof Building)) {
            $building = Building::findOrFail($id);
        }

        return new BuildingResource($building);
    }

    /**
     * Update the specified building in storage.
     */
    public function update(UpdateBuildingRequest $request, $building)
    {
        $id = $building instanceof Building ? $building->id : $building;

        if (LegacyAccess::isAvailable('ref_gedung')) {
            return DB::connection('legacy')->transaction(function () use ($request, $id) {
                $legacy = LegacyBuilding::findOrFail($id);
                $data = $request->validated();
                $legacy->update([
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'updated_at' => now(),
                ]);

                return new BuildingResource($legacy);
            });
        }

        if (! ($building instanceof Building)) {
            $building = Building::findOrFail($id);
        }

        return DB::transaction(function () use ($request, $building) {
            $building->update($request->validated());

            return new BuildingResource($building);
        });
    }

    /**
     * Remove the specified building from storage.
     */
    public function destroy($building)
    {
        $id = $building instanceof Building ? $building->id : $building;

        if (LegacyAccess::isAvailable('ref_gedung')) {
            $legacy = LegacyBuilding::findOrFail($id);
            if ($legacy->floors()->exists()) {
                return response()->json([
                    'message' => 'Gedung tidak dapat dihapus karena masih digunakan oleh data lantai.',
                ], 422);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($building instanceof Building)) {
            $building = Building::findOrFail($id);
        }

        if ($building->floors()->exists()) {
            return response()->json([
                'message' => 'Gedung tidak dapat dihapus karena masih digunakan oleh data lantai.',
            ], 422);
        }

        DB::transaction(function () use ($building) {
            $building->delete();
        });

        return response()->noContent();
    }
}
