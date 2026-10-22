<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreWardRequest;
use App\Http\Requests\MasterData\UpdateWardRequest;
use App\Http\Resources\MasterData\WardResource;
use App\Models\MasterData\Ward;
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
        return DB::transaction(function () use ($request) {
            $ward = Ward::create($request->validated());
            $ward->load(['floor.building']);

            return (new WardResource($ward))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified ward.
     */
    public function show(Ward $ward)
    {
        $ward->load(['floor.building']);

        return new WardResource($ward);
    }

    /**
     * Update the specified ward in storage.
     */
    public function update(UpdateWardRequest $request, Ward $ward)
    {
        return DB::transaction(function () use ($request, $ward) {
            $ward->update($request->validated());
            $ward->load(['floor.building']);

            return new WardResource($ward);
        });
    }

    /**
     * Remove the specified ward from storage.
     */
    public function destroy(Ward $ward)
    {
        if (method_exists($ward, 'rooms') && $ward->rooms()->exists()) {
            return response()->json([
                'message' => 'Bangsal tidak dapat dihapus karena masih digunakan oleh data ruangan.',
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
