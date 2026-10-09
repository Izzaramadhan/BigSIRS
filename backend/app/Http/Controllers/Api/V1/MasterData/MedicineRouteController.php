<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicineRouteRequest;
use App\Http\Requests\MasterData\UpdateMedicineRouteRequest;
use App\Http\Resources\MasterData\MedicineRouteResource;
use App\Models\MasterData\MedicineRoute;
use App\Models\MasterData\Medicine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MedicineRouteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', MedicineRoute::class);

        $query = MedicineRoute::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('object_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return MedicineRouteResource::collection(
            $query->orderBy('name')->paginate((int) $request->get('per_page', 15))
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicineRouteRequest $request)
    {
        $medicineRoute = MedicineRoute::create($request->validated());

        return new MedicineRouteResource($medicineRoute);
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicineRoute $medicineRoute)
    {
        Gate::authorize('view', $medicineRoute);

        return new MedicineRouteResource($medicineRoute);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicineRouteRequest $request, MedicineRoute $medicineRoute)
    {
        $medicineRoute->update($request->validated());

        return new MedicineRouteResource($medicineRoute);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicineRoute $medicineRoute): JsonResponse
    {
        Gate::authorize('delete', $medicineRoute);
        
        $isUsedInMedicines = Medicine::where('medicine_route_id', $medicineRoute->id)->exists();
        if ($isUsedInMedicines) {
            return response()->json([
                'message' => 'Jalur masuk obat tidak dapat dihapus karena sudah digunakan pada data obat.'
            ], 422);
        }

        $medicineRoute->delete();

        return response()->json(['message' => 'Medicine route deleted successfully']);
    }

    /**
     * Update status.
     */
    public function updateStatus(Request $request, MedicineRoute $medicineRoute)
    {
        Gate::authorize('update', $medicineRoute);

        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $medicineRoute->update(['is_active' => $request->is_active]);

        return new MedicineRouteResource($medicineRoute);
    }
}
