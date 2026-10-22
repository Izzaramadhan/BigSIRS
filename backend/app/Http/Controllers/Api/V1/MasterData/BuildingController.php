<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreBuildingRequest;
use App\Http\Requests\MasterData\UpdateBuildingRequest;
use App\Http\Resources\MasterData\BuildingResource;
use App\Models\MasterData\Building;
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
        return DB::transaction(function () use ($request) {
            $building = Building::create($request->validated());

            return (new BuildingResource($building))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified building.
     */
    public function show(Building $building)
    {
        return new BuildingResource($building);
    }

    /**
     * Update the specified building in storage.
     */
    public function update(UpdateBuildingRequest $request, Building $building)
    {
        return DB::transaction(function () use ($request, $building) {
            $building->update($request->validated());

            return new BuildingResource($building);
        });
    }

    /**
     * Remove the specified building from storage.
     */
    public function destroy(Building $building)
    {
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
