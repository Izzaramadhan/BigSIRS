<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicinePackageRequest;
use App\Http\Requests\MasterData\UpdateMedicinePackageRequest;
use App\Http\Resources\MasterData\MedicinePackageResource;
use App\Models\MasterData\MedicinePackage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MedicinePackageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', MedicinePackage::class);

        $query = MedicinePackage::query()->with('items.medicine');

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

        $allowedSorts = ['id', 'name', 'price', 'quantity', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($perPage === '-1' || $perPage === 'all') {
            return MedicinePackageResource::collection($query->get());
        }

        return MedicinePackageResource::collection(
            $query->paginate((int) $perPage)
        );
    }

    public function store(StoreMedicinePackageRequest $request): MedicinePackageResource
    {
        Gate::authorize('create', MedicinePackage::class);

        $medicinePackage = DB::transaction(function () use ($request) {
            $package = MedicinePackage::create($request->safe()->except('items'));
            
            foreach ($request->validated('items') as $item) {
                $package->items()->create($item);
            }
            
            return $package->load('items.medicine');
        });

        return new MedicinePackageResource($medicinePackage);
    }

    public function show(MedicinePackage $medicinePackage): MedicinePackageResource
    {
        Gate::authorize('view', $medicinePackage);

        return new MedicinePackageResource($medicinePackage->load('items.medicine'));
    }

    public function update(UpdateMedicinePackageRequest $request, MedicinePackage $medicinePackage): MedicinePackageResource
    {
        Gate::authorize('update', $medicinePackage);

        $medicinePackage = DB::transaction(function () use ($request, $medicinePackage) {
            $medicinePackage->update($request->safe()->except('items'));
            
            $medicinePackage->items()->delete();
            foreach ($request->validated('items') as $item) {
                $medicinePackage->items()->create($item);
            }
            
            return $medicinePackage->load('items.medicine');
        });

        return new MedicinePackageResource($medicinePackage);
    }

    public function destroy(MedicinePackage $medicinePackage): Response
    {
        Gate::authorize('delete', $medicinePackage);
        
        $medicinePackage->delete();

        return response()->noContent();
    }
}
