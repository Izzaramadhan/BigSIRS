<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreRoomClassRequest;
use App\Http\Requests\MasterData\UpdateRoomClassRequest;
use App\Http\Resources\MasterData\RoomClassResource;
use App\Models\Legacy\LegacyRoomClass;
use App\Models\MasterData\RoomClass;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomClassController extends Controller
{
    /**
     * Display a listing of room classes for dropdowns.
     */
    public function list(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_kelas')) {
            $classes = LegacyRoomClass::query()->orderBy('nama', 'asc')->get();

            $data = $classes->map(function ($c) {
                return [
                    'id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                    'display_name' => $c->code ? "{$c->code} - {$c->name}" : $c->name,
                    'price' => $c->price,
                    'description' => $c->description,
                    'aplicare_code' => $c->aplicare_code,
                    'sirs_code' => $c->sirs_code,
                ];
            });

            return response()->json([
                'data' => $data,
            ]);
        }

        $classes = RoomClass::query()->orderBy('name', 'asc')->get();

        $data = $classes->map(function ($c) {
            return [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'display_name' => "{$c->code} - {$c->name}",
                'price' => $c->price,
                'description' => $c->description,
                'aplicare_code' => $c->aplicare_code,
                'sirs_code' => $c->sirs_code,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Display a paginated listing of room classes.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        if (LegacyAccess::isAvailable('ref_kelas')) {
            $query = LegacyRoomClass::query()->orderBy('nama', 'asc');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($perPage === '-1' || $perPage === 'all') {
                return RoomClassResource::collection($query->get());
            }

            $perPageInt = min(max((int) $perPage, 1), 100);
            return RoomClassResource::collection($query->paginate($perPageInt));
        }

        $sort = $request->query('sort', $request->query('sortBy', 'name'));

        if ($request->has('direction')) {
            $direction = strtolower($request->query('direction')) === 'desc' ? 'desc' : 'asc';
        } else {
            $direction = filter_var($request->query('sortDesc', false), FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
        }

        $allowedSorts = ['id', 'code', 'name', 'price', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'name';
            $direction = 'asc';
        }

        $query = RoomClass::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return RoomClassResource::collection($query->orderBy($sort, $direction)->get());
        }

        $perPageInt = min(max((int) $perPage, 1), 100);
        $classes = $query->orderBy($sort, $direction)->paginate($perPageInt);

        return RoomClassResource::collection($classes);
    }

    /**
     * Store a newly created room class in storage.
     */
    public function store(StoreRoomClassRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_kelas')) {
            $user = $request->user();
            $legacyUserId = $user?->getLegacyUserId() ?? 1;

            return DB::connection('legacy')->transaction(function () use ($request, $legacyUserId) {
                $data = $request->validated();
                $roomClass = LegacyRoomClass::create([
                    'nama' => $data['name'],
                    'kode' => $data['code'] ?? null,
                    'harga' => $data['price'] ?? 0,
                    'deskripsi' => $data['description'] ?? null,
                    'kode_aplicares' => $data['aplicare_code'] ?? null,
                    'kode_sirs' => $data['sirs_code'] ?? null,
                    'status' => '1',
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return (new RoomClassResource($roomClass))->response()->setStatusCode(201);
            });
        }

        return DB::transaction(function () use ($request) {
            $roomClass = RoomClass::create($request->validated());

            return (new RoomClassResource($roomClass))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified room class.
     */
    public function show($roomClass)
    {
        $id = $roomClass instanceof RoomClass ? $roomClass->id : $roomClass;
        if (LegacyAccess::isAvailable('ref_kelas')) {
            $legacy = LegacyRoomClass::findOrFail($id);
            return new RoomClassResource($legacy);
        }

        if (! ($roomClass instanceof RoomClass)) {
            $roomClass = RoomClass::findOrFail($id);
        }

        return new RoomClassResource($roomClass);
    }

    /**
     * Update the specified room class in storage.
     */
    public function update(UpdateRoomClassRequest $request, $roomClass)
    {
        $id = $roomClass instanceof RoomClass ? $roomClass->id : $roomClass;

        if (LegacyAccess::isAvailable('ref_kelas')) {
            return DB::connection('legacy')->transaction(function () use ($request, $id) {
                $legacy = LegacyRoomClass::findOrFail($id);
                $data = $request->validated();
                $legacy->update([
                    'nama' => $data['name'],
                    'kode' => $data['code'] ?? null,
                    'harga' => $data['price'] ?? 0,
                    'deskripsi' => $data['description'] ?? null,
                    'kode_aplicares' => $data['aplicare_code'] ?? null,
                    'kode_sirs' => $data['sirs_code'] ?? null,
                    'updated_at' => now(),
                ]);

                return new RoomClassResource($legacy);
            });
        }

        if (! ($roomClass instanceof RoomClass)) {
            $roomClass = RoomClass::findOrFail($id);
        }

        return DB::transaction(function () use ($request, $roomClass) {
            $roomClass->update($request->validated());

            return new RoomClassResource($roomClass);
        });
    }

    /**
     * Remove the specified room class from storage.
     */
    public function destroy($roomClass)
    {
        $id = $roomClass instanceof RoomClass ? $roomClass->id : $roomClass;

        if (LegacyAccess::isAvailable('ref_kelas')) {
            $legacy = LegacyRoomClass::findOrFail($id);
            if ($legacy->rooms()->exists()) {
                return response()->json([
                    'message' => 'Data kelas tidak dapat dihapus karena masih digunakan oleh data kamar.',
                ], 422);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($roomClass instanceof RoomClass)) {
            $roomClass = RoomClass::findOrFail($id);
        }

        if ($roomClass->rooms()->exists()) {
            return response()->json([
                'message' => 'Data kelas tidak dapat dihapus karena masih digunakan oleh data kamar.',
            ], 422);
        }

        DB::transaction(function () use ($roomClass) {
            $roomClass->delete();
        });

        return response()->noContent();
    }
}
