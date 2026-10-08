<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Models\MasterData\RadiologyItem;
use App\Http\Requests\StoreRadiologyItemRequest;
use App\Http\Requests\UpdateRadiologyItemRequest;
use App\Http\Resources\RadiologyItemResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RadiologyItemController extends Controller
{
    public function index(Request $request)
    {
        $query = RadiologyItem::query()->with('group');

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('radiology_item_group_id') && $request->radiology_item_group_id !== '') {
            $query->where('radiology_item_group_id', $request->radiology_item_group_id);
        }

        $perPage = $request->input('per_page', 10);
        $items = $query->paginate($perPage);

        return RadiologyItemResource::collection($items);
    }

    public function store(StoreRadiologyItemRequest $request)
    {
        try {
            DB::beginTransaction();
            $item = RadiologyItem::create($request->validated());
            DB::commit();

            return (new RadiologyItemResource($item))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create radiology item',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(RadiologyItem $radiologyItem)
    {
        $radiologyItem->load('group');
        return new RadiologyItemResource($radiologyItem);
    }

    public function update(UpdateRadiologyItemRequest $request, RadiologyItem $radiologyItem)
    {
        try {
            DB::beginTransaction();
            $radiologyItem->update($request->validated());
            DB::commit();

            return new RadiologyItemResource($radiologyItem);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update radiology item',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(RadiologyItem $radiologyItem)
    {
        try {
            DB::beginTransaction();
            $radiologyItem->delete();
            DB::commit();

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete radiology item',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateStatus(Request $request, RadiologyItem $radiologyItem)
    {
        try {
            DB::beginTransaction();
            $radiologyItem->update(['is_active' => $request->is_active]);
            DB::commit();

            return response()->json([
                'message' => 'Status updated successfully',
                'data' => new RadiologyItemResource($radiologyItem)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update radiology item status',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
