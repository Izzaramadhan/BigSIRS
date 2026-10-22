<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreRoomClassRequest;
use App\Http\Resources\MasterData\RoomClassResource;
use App\Models\MasterData\RoomClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomClassController extends Controller
{
    /**
     * Display a listing of room classes for dropdown options.
     */
    public function options(Request $request)
    {
        $query = RoomClass::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $classes = $query->orderBy('name', 'asc')->get();

        $data = $classes->map(function ($c) {
            return [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'description' => $c->description,
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
        $sort = $request->query('sort', $request->query('sortBy', 'name'));

        if ($request->has('direction')) {
            $direction = strtolower($request->query('direction')) === 'desc' ? 'desc' : 'asc';
        } else {
            $direction = filter_var($request->query('sortDesc', false), FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
        }

        $allowedSorts = ['id', 'code', 'name', 'created_at', 'updated_at'];
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
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            if (empty($data['code'])) {
                // Auto generate code e.g. KLS-xx
                $maxId = RoomClass::withTrashed()->max('id') ?? 0;
                $generatedCode = 'KLS-' . str_pad((string) ($maxId + 1), 2, '0', STR_PAD_LEFT);
                // Ensure unique code
                while (RoomClass::withTrashed()->where('code', $generatedCode)->exists()) {
                    $maxId++;
                    $generatedCode = 'KLS-' . str_pad((string) ($maxId + 1), 2, '0', STR_PAD_LEFT);
                }
                $data['code'] = $generatedCode;
            }

            $roomClass = RoomClass::create($data);

            return (new RoomClassResource($roomClass))->response()->setStatusCode(201);
        });
    }

    /**
     * Display the specified room class.
     */
    public function show(RoomClass $roomClass)
    {
        return new RoomClassResource($roomClass);
    }

    /**
     * Remove the specified room class from storage.
     */
    public function destroy(RoomClass $roomClass)
    {
        if ($roomClass->rooms()->exists()) {
            return response()->json([
                'message' => 'Kelas tidak dapat dihapus karena masih digunakan oleh data kamar.',
            ], 422);
        }

        DB::transaction(function () use ($roomClass) {
            $roomClass->delete();
        });

        return response()->noContent();
    }
}
