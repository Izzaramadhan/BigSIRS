<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreRoomRequest;
use App\Http\Requests\MasterData\UpdateRoomRequest;
use App\Http\Resources\MasterData\RoomResource;
use App\Models\Legacy\LegacyRoom;
use App\Models\MasterData\Room;
use App\Support\LegacyAccess;
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

        if (LegacyAccess::isAvailable('ref_kamar')) {
            $query = LegacyRoom::with(['ward.floor.building', 'roomClass'])->orderBy('nama', 'asc');

            if ($request->filled('ward_id')) {
                $query->where('id_bangsal', $request->ward_id);
            }

            if ($request->filled('room_class_id')) {
                $query->where('id_kelas', $request->room_class_id);
            }

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($perPage === '-1' || $perPage === 'all') {
                return RoomResource::collection($query->get());
            }

            $perPageInt = min(max((int) $perPage, 1), 100);
            return RoomResource::collection($query->paginate($perPageInt));
        }

        $sort = $request->query('sort', $request->query('sortBy', 'created_at'));

        if ($request->has('direction')) {
            $direction = strtolower($request->query('direction')) === 'desc' ? 'desc' : 'asc';
        } else {
            $direction = filter_var($request->query('sortDesc', false), FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
        }

        $allowedSorts = ['id', 'code', 'name', 'ward_id', 'room_class_id', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
            $direction = 'desc';
        }

        $query = Room::with(['ward.floor.building', 'roomClass']);

        if ($request->filled('ward_id')) {
            $query->where('ward_id', $request->ward_id);
        }

        if ($request->filled('room_class_id')) {
            $query->where('room_class_id', $request->room_class_id);
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
        if (LegacyAccess::isAvailable('ref_kamar')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            return DB::connection('legacy')->transaction(function () use ($request, $legacyUserId) {
                $data = $request->validated();
                $room = LegacyRoom::create([
                    'id_bangsal' => $data['ward_id'],
                    'id_kelas' => $data['room_class_id'] ?? $data['class_id'],
                    'kode' => $data['code'] ?? null,
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'status' => '1',
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return (new RoomResource($room->load(['ward.floor.building', 'roomClass'])))->response()->setStatusCode(201);
            });
        }

        return DB::transaction(function () use ($request) {
            $room = Room::create($request->validated());
            $room->load(['ward.floor.building', 'roomClass']);

            return (new RoomResource($room))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified room.
     */
    public function show($room)
    {
        $id = $room instanceof Room ? $room->id : $room;
        if (LegacyAccess::isAvailable('ref_kamar')) {
            $legacy = LegacyRoom::with(['ward.floor.building', 'roomClass'])->findOrFail($id);
            return new RoomResource($legacy);
        }

        if (! ($room instanceof Room)) {
            $room = Room::findOrFail($id);
        }

        $room->load(['ward.floor.building', 'roomClass']);

        return new RoomResource($room);
    }

    /**
     * Update the specified room in storage.
     */
    public function update(UpdateRoomRequest $request, $room)
    {
        $id = $room instanceof Room ? $room->id : $room;

        if (LegacyAccess::isAvailable('ref_kamar')) {
            return DB::connection('legacy')->transaction(function () use ($request, $id) {
                $legacy = LegacyRoom::findOrFail($id);
                $data = $request->validated();
                $legacy->update([
                    'id_bangsal' => $data['ward_id'],
                    'id_kelas' => $data['room_class_id'] ?? $data['class_id'],
                    'kode' => $data['code'] ?? null,
                    'nama' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'updated_at' => now(),
                ]);

                return new RoomResource($legacy->load(['ward.floor.building', 'roomClass']));
            });
        }

        if (! ($room instanceof Room)) {
            $room = Room::findOrFail($id);
        }

        return DB::transaction(function () use ($request, $room) {
            $room->update($request->validated());
            $room->load(['ward.floor.building', 'roomClass']);

            return new RoomResource($room);
        });
    }

    /**
     * Remove the specified room from storage.
     */
    public function destroy($room)
    {
        $id = $room instanceof Room ? $room->id : $room;

        if (LegacyAccess::isAvailable('ref_kamar')) {
            $legacy = LegacyRoom::findOrFail($id);
            if ($legacy->beds()->exists()) {
                return response()->json([
                    'message' => 'Kamar tidak dapat dihapus karena masih digunakan oleh data tempat tidur (bed).',
                ], 422);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($room instanceof Room)) {
            $room = Room::findOrFail($id);
        }

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
