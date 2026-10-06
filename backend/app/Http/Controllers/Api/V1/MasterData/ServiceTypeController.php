<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterData\ServiceTypeRequest;
use App\Http\Resources\Api\V1\MasterData\ServiceTypeResource;
use App\Models\ServiceType;
use App\Services\MasterData\ServiceTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceTypeController extends Controller
{
    protected ServiceTypeService $service;

    public function __construct(ServiceTypeService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'is_active']);
        $perPage = $request->integer('per_page', 10);
        $sort = $request->string('sort', 'name')->toString();
        $order = $request->string('order', 'asc')->toString();

        $serviceTypes = $this->service->getPaginated($filters, $perPage, $sort, $order);

        return ServiceTypeResource::collection($serviceTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ServiceTypeRequest $request): JsonResponse
    {
        $serviceType = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Data jenis layanan berhasil ditambahkan',
            'data' => new ServiceTypeResource($serviceType)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceType $serviceType): ServiceTypeResource
    {
        return new ServiceTypeResource($serviceType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ServiceTypeRequest $request, ServiceType $serviceType): JsonResponse
    {
        $serviceType = $this->service->update($serviceType, $request->validated());

        return response()->json([
            'message' => 'Data jenis layanan berhasil diperbarui',
            'data' => new ServiceTypeResource($serviceType)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceType $serviceType): JsonResponse
    {
        $this->service->delete($serviceType);

        return response()->json(null, 204);
    }

    /**
     * Update the active status of the specified resource.
     */
    public function updateStatus(Request $request, ServiceType $serviceType): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $serviceType = $this->service->update($serviceType, $validated);

        return response()->json([
            'message' => 'Status jenis layanan berhasil diperbarui',
            'data' => new ServiceTypeResource($serviceType)
        ]);
    }
}
