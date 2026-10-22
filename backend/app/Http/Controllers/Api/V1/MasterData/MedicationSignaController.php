<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicationSignaRequest;
use App\Http\Requests\MasterData\UpdateMedicationSignaRequest;
use App\Http\Resources\MasterData\MedicationSignaResource;
use App\Models\MasterData\MedicationSigna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MedicationSignaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', MedicationSigna::class);

        $query = MedicationSigna::query();

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort', $request->input('sortBy', 'name'));
        if ($request->has('direction')) {
            $sortDesc = strtolower($request->input('direction')) === 'desc';
        } else {
            $sortDesc = filter_var($request->input('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        }

        $allowedSorts = ['id', 'name', 'is_active', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return MedicationSignaResource::collection($query->get());
        }

        $medicationSignas = $query->paginate((int) $perPage);

        return MedicationSignaResource::collection($medicationSignas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicationSignaRequest $request): MedicationSignaResource
    {
        Gate::authorize('create', MedicationSigna::class);

        $medicationSigna = MedicationSigna::create($request->validated());

        return new MedicationSignaResource($medicationSigna);
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicationSigna $medicationSigna): MedicationSignaResource
    {
        Gate::authorize('view', $medicationSigna);

        return new MedicationSignaResource($medicationSigna);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicationSignaRequest $request, MedicationSigna $medicationSigna): MedicationSignaResource
    {
        Gate::authorize('update', $medicationSigna);

        $medicationSigna->update($request->validated());

        return new MedicationSignaResource($medicationSigna);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicationSigna $medicationSigna): JsonResponse
    {
        Gate::authorize('delete', $medicationSigna);

        $medicationSigna->delete();

        return response()->json(['message' => 'Signa obat berhasil dihapus.']);
    }

    /**
     * Update the active status of the specified resource.
     */
    public function updateStatus(Request $request, MedicationSigna $medicationSigna): MedicationSignaResource
    {
        Gate::authorize('update', $medicationSigna);

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $medicationSigna->update($validated);

        return new MedicationSignaResource($medicationSigna);
    }
}
