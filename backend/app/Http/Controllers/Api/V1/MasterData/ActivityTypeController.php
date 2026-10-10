<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterData\StoreActivityTypeRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateActivityTypeRequest;
use App\Http\Resources\Api\V1\MasterData\ActivityTypeResource;
use App\Models\ActivityType;
use App\Services\MasterData\ActivityTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityTypeController extends Controller
{
    protected ActivityTypeService $activityTypeService;

    public function __construct(ActivityTypeService $activityTypeService)
    {
        $this->activityTypeService = $activityTypeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 10);
        $filters = $request->only(['search', 'parent_id']);

        $activityTypes = $this->activityTypeService->getPaginated($filters, $perPage);

        return ActivityTypeResource::collection($activityTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityTypeRequest $request): JsonResponse
    {
        $activityType = $this->activityTypeService->create($request->validated());

        return response()->json([
            'message' => 'Jenis kegiatan berhasil ditambahkan',
            'data' => new ActivityTypeResource($activityType),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ActivityType $activityType): ActivityTypeResource
    {
        return new ActivityTypeResource($activityType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityTypeRequest $request, ActivityType $activityType): JsonResponse
    {
        $activityType = $this->activityTypeService->update($activityType, $request->validated());

        return response()->json([
            'message' => 'Jenis kegiatan berhasil diperbarui',
            'data' => new ActivityTypeResource($activityType),
        ]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ActivityType $activityType): JsonResponse
    {
        $this->activityTypeService->delete($activityType);

        return response()->json([
            'message' => 'Jenis kegiatan berhasil dihapus',
        ]);
    }

    /**
     * Get lookup data for parent selection.
     */
    public function lookup(Request $request): JsonResponse
    {
        $excludeId = $request->input('exclude_id');
        $query = ActivityType::query();

        $all = $query->orderBy('name', 'asc')->get();

        $descendants = [];
        if ($excludeId) {
            $descendants[] = $excludeId;
            $this->getDescendantsIds($all, $excludeId, $descendants);
        }

        // Build flat tree with indentation
        $tree = [];
        $this->buildFlatTree($all, null, 0, $tree, $descendants);

        return response()->json([
            'data' => $tree,
        ]);
    }

    private function getDescendantsIds($collection, $parentId, &$result)
    {
        foreach ($collection as $item) {
            if ($item->parent_id == $parentId) {
                $result[] = $item->id;
                $this->getDescendantsIds($collection, $item->id, $result);
            }
        }
    }

    private function buildFlatTree($collection, $parentId, $level, &$result, $excludeIds)
    {
        foreach ($collection as $item) {
            if ($item->parent_id == $parentId && ! in_array($item->id, $excludeIds)) {
                $prefix = str_repeat('-', $level * 2);
                $prefix = $prefix ? $prefix.' ' : '';

                $result[] = [
                    'id' => $item->id,
                    'name' => $prefix.$item->name,
                    'original_name' => $item->name,
                ];

                $this->buildFlatTree($collection, $item->id, $level + 1, $result, $excludeIds);
            }
        }
    }
}
