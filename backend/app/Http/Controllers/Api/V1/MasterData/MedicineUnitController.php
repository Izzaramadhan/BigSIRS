<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicineUnitRequest;
use App\Http\Requests\MasterData\UpdateMedicineUnitRequest;
use App\Http\Resources\MasterData\MedicineUnitResource;
use App\Models\MasterData\MedicineUnit;
use Illuminate\Http\Request;

class MedicineUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MedicineUnit::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        
        $sortDesc = filter_var($request->get('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        $sortBy = $request->get('sortBy', 'created_at');
        $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');

        $perPage = $request->get('per_page', 10);
        
        if ($perPage === '-1' || $perPage === 'all') {
            return MedicineUnitResource::collection($query->get());
        }

        return MedicineUnitResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicineUnitRequest $request)
    {
        $medicineUnit = MedicineUnit::create($request->validated());

        return new MedicineUnitResource($medicineUnit);
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicineUnit $medicineUnit)
    {
        return new MedicineUnitResource($medicineUnit);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicineUnitRequest $request, MedicineUnit $medicineUnit)
    {
        $medicineUnit->update($request->validated());

        return new MedicineUnitResource($medicineUnit);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicineUnit $medicineUnit)
    {
        $medicineUnit->delete();

        return response()->noContent();
    }
}
