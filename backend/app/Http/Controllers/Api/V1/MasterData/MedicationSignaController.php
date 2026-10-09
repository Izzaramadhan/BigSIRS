<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicationSignaRequest;
use App\Http\Requests\MasterData\UpdateMedicationSignaRequest;
use App\Http\Resources\MasterData\MedicationSignaResource;
use App\Models\MasterData\MedicationSigna;
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

        return MedicationSignaResource::collection(
            $query->orderBy('name')->paginate((int) $request->get('per_page', 15))
        );
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
    public function destroy(MedicationSigna $medicationSigna): Response
    {
        Gate::authorize('delete', $medicationSigna);

        // Check for dependencies (if prescriptions use this signa via foreign key)
        // For now, based on legacy audit, signa might be stored as text, but if there's a reference:
        // if ($medicationSigna->prescriptions()->exists()) {
        //     abort(409, 'Signa obat tidak dapat dihapus karena sudah digunakan pada resep atau data pemberian obat.');
        // }

        $medicationSigna->delete();

        return response()->noContent();
    }
}
