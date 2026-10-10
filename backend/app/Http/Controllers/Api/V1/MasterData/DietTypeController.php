<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterData\StoreDietTypeRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateDietTypeRequest;
use App\Http\Resources\Api\V1\MasterData\DietTypeResource;
use App\Models\DietType;
use App\Services\MasterData\DietTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DietTypeController extends Controller
{
    protected DietTypeService $dietTypeService;

    public function __construct(DietTypeService $dietTypeService)
    {
        $this->dietTypeService = $dietTypeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 10);
        $filters = $request->only(['search']);

        $dietTypes = $this->dietTypeService->getPaginated($filters, $perPage);

        return DietTypeResource::collection($dietTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDietTypeRequest $request): JsonResponse
    {
        $dietType = $this->dietTypeService->create($request->validated());

        return response()->json([
            'message' => 'Asuhan gizi berhasil ditambahkan',
            'data' => new DietTypeResource($dietType),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(DietType $dietType): DietTypeResource
    {
        return new DietTypeResource($dietType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDietTypeRequest $request, DietType $dietType): JsonResponse
    {
        $dietType = $this->dietTypeService->update($dietType, $request->validated());

        return response()->json([
            'message' => 'Asuhan gizi berhasil diperbarui',
            'data' => new DietTypeResource($dietType),
        ]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DietType $dietType): JsonResponse
    {
        $this->dietTypeService->delete($dietType);

        return response()->json([
            'message' => 'Asuhan gizi berhasil dihapus',
        ]);
    }
}
