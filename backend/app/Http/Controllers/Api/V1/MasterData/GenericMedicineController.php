<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreGenericMedicineRequest;
use App\Http\Resources\MasterData\GenericMedicineResource;
use App\Models\MasterData\GenericMedicine;
use Illuminate\Http\Request;

class GenericMedicineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = GenericMedicine::query();

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'like', '%'.$search.'%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sortBy = $request->get('sort', $request->get('sortBy', 'name'));
        if ($request->has('direction')) {
            $sortDesc = strtolower($request->get('direction')) === 'desc';
        } else {
            $sortDesc = filter_var($request->get('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        }

        $allowedSorts = ['id', 'name', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'name';
        }

        $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');

        $perPage = $request->get('per_page', 10);

        if ($perPage === '-1' || $perPage === 'all') {
            return GenericMedicineResource::collection($query->get());
        }

        return GenericMedicineResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGenericMedicineRequest $request)
    {
        $name = preg_replace('/\s+/', ' ', trim($request->validated('name')));

        // Check again for concurrency/race conditions
        $existing = GenericMedicine::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return (new GenericMedicineResource($existing))
                ->additional(['message' => 'Data generik sudah tersedia.'])
                ->response()
                ->setStatusCode(200);
        }

        $generic = GenericMedicine::create([
            'legacy_id' => null,
            'name' => $name,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return (new GenericMedicineResource($generic))
            ->additional(['message' => 'Data generik berhasil ditambahkan.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(GenericMedicine $genericMedicine)
    {
        return new GenericMedicineResource($genericMedicine);
    }

    /**
     * Update the active status of the specified resource.
     */
    public function updateStatus(Request $request, GenericMedicine $genericMedicine)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $genericMedicine->update($validated);

        return new GenericMedicineResource($genericMedicine);
    }
}
