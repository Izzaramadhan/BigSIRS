<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Models\Polyclinic;
use App\Http\Resources\PolyclinicResource;
use App\Http\Requests\StorePolyclinicRequest;
use App\Http\Requests\UpdatePolyclinicRequest;
use App\Http\Requests\UpdatePolyclinicStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PolyclinicController extends Controller
{
    public function index(Request $request)
    {
        $perPage   = min((int) $request->query('per_page', 15), 100);
        $sort      = $request->query('sort', 'name');
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'code', 'name', 'service_type', 'quota', 'jkn_quota', 'bpjs_code', 'is_active', 'created_at', 'updated_at'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        $query = Polyclinic::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        $polyclinics = $query->orderBy($sort, $direction)->paginate($perPage);

        return PolyclinicResource::collection($polyclinics);
    }

    public function store(StorePolyclinicRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            // Ensure parent_id is never set from user form
            unset($data['parent_id']);

            $polyclinic = Polyclinic::create($data);
            return (new PolyclinicResource($polyclinic))->response()->setStatusCode(201);
        });
    }

    public function show(Polyclinic $polyclinic)
    {
        return new PolyclinicResource($polyclinic);
    }

    public function update(UpdatePolyclinicRequest $request, Polyclinic $polyclinic)
    {
        return DB::transaction(function () use ($request, $polyclinic) {
            $data = $request->validated();
            // Ensure parent_id is never modified from user form
            unset($data['parent_id']);

            $polyclinic->update($data);
            return new PolyclinicResource($polyclinic->fresh());
        });
    }

    public function status(UpdatePolyclinicStatusRequest $request, Polyclinic $polyclinic)
    {
        return DB::transaction(function () use ($request, $polyclinic) {
            $polyclinic->update($request->validated());
            return new PolyclinicResource($polyclinic->fresh());
        });
    }

    public function destroy(Polyclinic $polyclinic)
    {
        if ($polyclinic->children()->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus poliklinik karena memiliki sub-poliklinik aktif.'
            ], 409);
        }

        DB::transaction(function () use ($polyclinic) {
            $polyclinic->delete();
        });

        return response()->noContent();
    }

    /**
     * Return all ServiceType enum values for frontend dropdowns.
     */
    public function serviceTypes()
    {
        return response()->json([
            'data' => collect(ServiceType::cases())->map(fn ($e) => [
                'value' => $e->value,
                'label' => $e->label(),
            ])
        ]);
    }
}
