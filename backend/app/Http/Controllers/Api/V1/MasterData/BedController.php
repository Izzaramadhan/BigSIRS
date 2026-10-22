<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreBedRequest;
use App\Http\Requests\MasterData\UpdateBedRequest;
use App\Http\Resources\MasterData\BedResource;
use App\Models\MasterData\Bed;
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
        return DB::transaction(function () use ($request) {
            $bed = Bed::create($request->validated());
            $bed->load(['room.ward.floor.building', 'room.roomClass']);

            return (new BedResource($bed))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified bed.
     */
    public function show(Bed $bed)
    {
        $bed->load(['room.ward.floor.building', 'room.roomClass']);

        return new BedResource($bed);
    }

    /**
     * Update the specified bed in storage.
     */
    public function update(UpdateBedRequest $request, Bed $bed)
    {
        return DB::transaction(function () use ($request, $bed) {
            $bed->update($request->validated());
            $bed->load(['room.ward.floor.building', 'room.roomClass']);

            return new BedResource($bed);
        });
    }

    /**
     * Remove the specified bed from storage.
     */
    public function destroy(Bed $bed)
    {
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
