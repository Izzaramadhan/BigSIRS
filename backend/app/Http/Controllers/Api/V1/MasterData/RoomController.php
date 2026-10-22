<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreRoomRequest;
use App\Http\Requests\MasterData\UpdateRoomRequest;
use App\Http\Resources\MasterData\RoomResource;
use App\Models\MasterData\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    /**
     * Display a listing of the rooms.
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

        $allowedSorts = ['id', 'code', 'name', 'ward_id', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
            $direction = 'desc';
        }

        $query = Room::with(['ward.floor.building']);

        if ($request->filled('ward_id')) {
            $query->where('ward_id', $request->ward_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return RoomResource::collection($query->orderBy($sort, $direction)->get());
        }

        $perPageInt = min(max((int) $perPage, 1), 100);
        $rooms = $query->orderBy($sort, $direction)->paginate($perPageInt);

        return RoomResource::collection($rooms);
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(StoreRoomRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $room = Room::create($request->validated());
            $room->load(['ward.floor.building']);

            return (new RoomResource($room))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified room.
     */
    public function show(Room $room)
    {
        $room->load(['ward.floor.building']);

        return new RoomResource($room);
    }

    /**
     * Update the specified room in storage.
     */
    public function update(UpdateRoomRequest $request, Room $room)
    {
        return DB::transaction(function () use ($request, $room) {
            $room->update($request->validated());
            $room->load(['ward.floor.building']);

            return new RoomResource($room);
        });
    }

    /**
     * Remove the specified room from storage.
     */
    public function destroy(Room $room)
    {
        if (method_exists($room, 'beds') && $room->beds()->exists()) {
            return response()->json([
                'message' => 'Kamar tidak dapat dihapus karena masih digunakan oleh data tempat tidur (bed).',
            ], 422);
        }

        DB::transaction(function () use ($room) {
            $room->delete();
        });

        return response()->noContent();
    }
}
