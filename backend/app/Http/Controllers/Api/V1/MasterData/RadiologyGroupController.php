<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RadiologyGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\MasterData\RadiologyGroup::with(['category', 'type', 'activityType'])
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

        return \App\Http\Resources\RadiologyGroupResource::collection($groups);
    }

    public function store(\App\Http\Requests\StoreRadiologyGroupRequest $request)
    {
        $group = \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $group = \App\Models\MasterData\RadiologyGroup::create($request->validated());

            if ($request->has('radiology_item_group_ids')) {
                $group->itemGroups()->sync($request->radiology_item_group_ids);
            }

            return $group->load(['category', 'type', 'activityType', 'itemGroups']);
        });

        return new \App\Http\Resources\RadiologyGroupResource($group);
    }

    public function show(\App\Models\MasterData\RadiologyGroup $radiologyGroup)
    {
        return new \App\Http\Resources\RadiologyGroupResource($radiologyGroup->load(['category', 'type', 'activityType', 'itemGroups']));
    }

    public function update(\App\Http\Requests\UpdateRadiologyGroupRequest $request, \App\Models\MasterData\RadiologyGroup $radiologyGroup)
    {
        $group = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $radiologyGroup) {
            $radiologyGroup->update($request->validated());

            if ($request->has('radiology_item_group_ids')) {
                $radiologyGroup->itemGroups()->sync($request->radiology_item_group_ids);
            }

            return $radiologyGroup->load(['category', 'type', 'activityType', 'itemGroups']);
        });

        return new \App\Http\Resources\RadiologyGroupResource($group);
    }

    public function destroy(\App\Models\MasterData\RadiologyGroup $radiologyGroup)
    {
        $radiologyGroup->delete();
        return response()->noContent();
    }

    public function updateStatus(Request $request, \App\Models\MasterData\RadiologyGroup $radiologyGroup)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $radiologyGroup->update(['is_active' => $request->is_active]);
        return new \App\Http\Resources\RadiologyGroupResource($radiologyGroup);
    }
}
