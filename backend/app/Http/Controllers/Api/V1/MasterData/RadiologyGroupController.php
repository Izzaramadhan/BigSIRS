<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRadiologyGroupRequest;
use App\Http\Requests\UpdateRadiologyGroupRequest;
use App\Http\Resources\RadiologyGroupResource;
use App\Models\MasterData\RadiologyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RadiologyGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = RadiologyGroup::with(['category', 'type', 'activityType'])
            ->withCount('itemGroups')
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('loinc_code', 'like', "%{$search}%");
            })
            ->when($request->category_id, function ($q, $categoryId) {
                $q->where('radiology_category_id', $categoryId);
            })
            ->when($request->type_id, function ($q, $typeId) {
                $q->where('radiology_type_id', $typeId);
            });

        $sort = $request->sort ?: 'id';
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        $groups = $query->orderBy($sort, $direction)
            ->paginate($request->per_page ?: 10);

        return RadiologyGroupResource::collection($groups);
    }

    public function store(StoreRadiologyGroupRequest $request)
    {
        $group = DB::transaction(function () use ($request) {
            $group = RadiologyGroup::create($request->validated());

            if ($request->has('radiology_item_group_ids')) {
                $group->itemGroups()->sync($request->radiology_item_group_ids);
            }

            return $group->load(['category', 'type', 'activityType', 'itemGroups']);
        });

        return new RadiologyGroupResource($group);
    }

    public function show(RadiologyGroup $radiologyGroup)
    {
        return new RadiologyGroupResource($radiologyGroup->load(['category', 'type', 'activityType', 'itemGroups']));
    }

    public function update(UpdateRadiologyGroupRequest $request, RadiologyGroup $radiologyGroup)
    {
        $group = DB::transaction(function () use ($request, $radiologyGroup) {
            $radiologyGroup->update($request->validated());

            if ($request->has('radiology_item_group_ids')) {
                $radiologyGroup->itemGroups()->sync($request->radiology_item_group_ids);
            }

            return $radiologyGroup->load(['category', 'type', 'activityType', 'itemGroups']);
        });

        return new RadiologyGroupResource($group);
    }

    public function destroy(RadiologyGroup $radiologyGroup)
    {
        $radiologyGroup->delete();

        return response()->noContent();
    }

    public function updateStatus(Request $request, RadiologyGroup $radiologyGroup)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $radiologyGroup->update(['is_active' => $request->is_active]);

        return new RadiologyGroupResource($radiologyGroup);
    }
}
