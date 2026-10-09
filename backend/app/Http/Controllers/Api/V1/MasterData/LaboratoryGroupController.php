<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLaboratoryGroupRequest;
use App\Http\Requests\UpdateLaboratoryGroupRequest;
use App\Http\Resources\MasterData\LaboratoryGroupResource;
use App\Models\MasterData\LaboratoryGroup;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class LaboratoryGroupController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', LaboratoryGroup::class);

        $groups = LaboratoryGroup::query()
            ->with(['category'])
            ->withCount('items')
            ->search($request->input('search'))
            ->when($request->input('laboratory_category_id'), function ($query, $categoryId) {
                $query->where('laboratory_category_id', $categoryId);
            })
            ->when($request->input('sort_by'), function ($query, $sortBy) use ($request) {
                $sortDesc = $request->boolean('sort_desc', false);
                $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
            }, function ($query) {
                $query->orderBy('name');
            })
            ->paginate($request->integer('per_page', 10));

        return LaboratoryGroupResource::collection($groups);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLaboratoryGroupRequest $request): LaboratoryGroupResource
    {
        $this->authorize('create', LaboratoryGroup::class);

        $group = DB::transaction(function () use ($request) {
            $group = LaboratoryGroup::create($request->validated());

            if ($request->has('laboratory_item_ids')) {
                $group->items()->sync($request->input('laboratory_item_ids'));
            }

            return $group;
        });

        $group->load(['category', 'items']);
        $group->loadCount('items');

        return new LaboratoryGroupResource($group);
    }

    /**
     * Display the specified resource.
     */
    public function show(LaboratoryGroup $laboratoryGroup): LaboratoryGroupResource
    {
        $this->authorize('view', $laboratoryGroup);

        $laboratoryGroup->load(['category', 'items']);
        $laboratoryGroup->loadCount('items');

        return new LaboratoryGroupResource($laboratoryGroup);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLaboratoryGroupRequest $request, LaboratoryGroup $laboratoryGroup): LaboratoryGroupResource
    {
        $this->authorize('update', $laboratoryGroup);

        DB::transaction(function () use ($request, $laboratoryGroup) {
            $laboratoryGroup->update($request->validated());

            if ($request->has('laboratory_item_ids')) {
                $laboratoryGroup->items()->sync($request->input('laboratory_item_ids'));
            }
        });

        $laboratoryGroup->load(['category', 'items']);
        $laboratoryGroup->loadCount('items');

        return new LaboratoryGroupResource($laboratoryGroup);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LaboratoryGroup $laboratoryGroup)
    {
        $this->authorize('delete', $laboratoryGroup);

        $laboratoryGroup->delete();

        return response()->noContent();
    }
}
