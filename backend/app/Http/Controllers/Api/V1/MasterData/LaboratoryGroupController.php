<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLaboratoryGroupRequest;
use App\Http\Requests\UpdateLaboratoryGroupRequest;
use App\Http\Resources\MasterData\LaboratoryGroupResource;
use App\Models\Legacy\LegacyLaboratoryGroup;
use App\Models\MasterData\LaboratoryGroup;
use App\Support\LegacyAccess;
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

        if (LegacyAccess::isAvailable('ref_grup_lab')) {
            $query = LegacyLaboratoryGroup::with(['category', 'items'])->withCount('items');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('laboratory_category_id')) {
                $query->where('id_kategori_lab', $request->laboratory_category_id);
            }

            if ($request->has('is_active') && $request->input('is_active') !== '' && $request->input('is_active') !== null) {
                $isActive = $request->boolean('is_active');
                $query->where('status', $isActive ? 1 : 0);
            }

            $perPage = $request->integer('per_page', 10);
            return LaboratoryGroupResource::collection($query->paginate($perPage));
        }

        $groups = LaboratoryGroup::query()
            ->with(['category', 'items'])
            ->withCount('items')
            ->search($request->input('search'))
            ->when($request->input('laboratory_category_id'), function ($query, $categoryId) {
                $query->where('laboratory_category_id', $categoryId);
            })
            ->when($request->has('is_active') && $request->input('is_active') !== '' && $request->input('is_active') !== null, function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->when($request->input('sort_by') ?? $request->input('sortBy'), function ($query, $sortBy) use ($request) {
                $sortDesc = $request->boolean('sort_desc', false) || $request->boolean('sortDesc', false);
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

        if (LegacyAccess::isAvailable('ref_grup_lab')) {
            $data = $request->validated();
            $group = DB::connection('legacy')->transaction(function () use ($data, $request) {
                $group = LegacyLaboratoryGroup::create([
                    'nama' => $data['name'],
                    'id_kategori_lab' => $data['laboratory_category_id'],
                    'deskripsi' => $data['description'] ?? null,
                    'harga' => $data['price'] ?? 0,
                    'harga_jaspel' => $data['harga_jaspel'] ?? null,
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? '1' : '0') : '1',
                    'loinc_code' => $data['loinc_code'] ?? null,
                    'loinc_url' => $data['loinc_url'] ?? null,
                    'snomed_code' => $data['snomed_code'] ?? null,
                    'snomed_url' => $data['snomed_url'] ?? null,
                    'id_user' => auth()->id() ?? 1,
                ]);

                if ($request->has('laboratory_item_ids')) {
                    $itemIds = (array) $request->input('laboratory_item_ids');
                    foreach ($itemIds as $itemId) {
                        DB::connection('legacy')->table('map_grup_item_lab')->insert([
                            'id_grup' => $group->id,
                            'id_item' => $itemId,
                            'status' => '1',
                            'created_at' => now(),
                        ]);
                    }
                }

                return $group;
            });

            $group->load(['category', 'items']);
            $group->loadCount('items');

            return new LaboratoryGroupResource($group);
        }

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
    public function show($laboratoryGroup): LaboratoryGroupResource
    {
        $id = $laboratoryGroup instanceof LegacyLaboratoryGroup || $laboratoryGroup instanceof LaboratoryGroup
            ? $laboratoryGroup->id
            : $laboratoryGroup;

        if (LegacyAccess::isAvailable('ref_grup_lab')) {
            $legacy = LegacyLaboratoryGroup::with(['category', 'items'])->findOrFail($id);
            return new LaboratoryGroupResource($legacy);
        }

        if (! ($laboratoryGroup instanceof LaboratoryGroup)) {
            $laboratoryGroup = LaboratoryGroup::findOrFail($id);
        }

        $this->authorize('view', $laboratoryGroup);

        $laboratoryGroup->load(['category', 'items']);
        $laboratoryGroup->loadCount('items');

        return new LaboratoryGroupResource($laboratoryGroup);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLaboratoryGroupRequest $request, $laboratoryGroup): LaboratoryGroupResource
    {
        $id = $laboratoryGroup instanceof LegacyLaboratoryGroup || $laboratoryGroup instanceof LaboratoryGroup
            ? $laboratoryGroup->id
            : $laboratoryGroup;

        if (LegacyAccess::isAvailable('ref_grup_lab')) {
            $legacy = LegacyLaboratoryGroup::findOrFail($id);
            $this->authorize('update', $legacy);

            $data = $request->validated();
            DB::connection('legacy')->transaction(function () use ($data, $request, $legacy) {
                $updateData = [
                    'nama' => $data['name'],
                    'id_kategori_lab' => $data['laboratory_category_id'],
                    'deskripsi' => $data['description'] ?? null,
                    'harga' => $data['price'] ?? 0,
                    'harga_jaspel' => $data['harga_jaspel'] ?? null,
                    'loinc_code' => $data['loinc_code'] ?? null,
                    'loinc_url' => $data['loinc_url'] ?? null,
                    'snomed_code' => $data['snomed_code'] ?? null,
                    'snomed_url' => $data['snomed_url'] ?? null,
                ];
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? '1' : '0';
                }
                $legacy->update($updateData);

                if ($request->has('laboratory_item_ids')) {
                    $itemIds = array_map('intval', (array) $request->input('laboratory_item_ids'));
                    DB::connection('legacy')->table('map_grup_item_lab')
                        ->where('id_grup', $legacy->id)
                        ->whereNotIn('id_item', $itemIds)
                        ->delete();

                    $existing = DB::connection('legacy')->table('map_grup_item_lab')
                        ->where('id_grup', $legacy->id)
                        ->pluck('id_item')
                        ->map(fn ($val) => (int) $val)
                        ->toArray();

                    foreach ($itemIds as $itemId) {
                        if (! in_array($itemId, $existing, true)) {
                            DB::connection('legacy')->table('map_grup_item_lab')->insert([
                                'id_grup' => $legacy->id,
                                'id_item' => $itemId,
                                'status' => '1',
                                'created_at' => now(),
                            ]);
                        }
                    }
                }
            });

            $legacy->load(['category', 'items']);
            $legacy->loadCount('items');

            return new LaboratoryGroupResource($legacy);
        }

        if (! ($laboratoryGroup instanceof LaboratoryGroup)) {
            $laboratoryGroup = LaboratoryGroup::findOrFail($id);
        }

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
    public function destroy($laboratoryGroup)
    {
        $id = $laboratoryGroup instanceof LegacyLaboratoryGroup || $laboratoryGroup instanceof LaboratoryGroup
            ? $laboratoryGroup->id
            : $laboratoryGroup;

        if (LegacyAccess::isAvailable('ref_grup_lab')) {
            $legacy = LegacyLaboratoryGroup::findOrFail($id);
            $this->authorize('delete', $legacy);

            DB::connection('legacy')->table('map_grup_item_lab')
                ->where('id_grup', $legacy->id)
                ->delete();

            $legacy->delete();

            return response()->noContent();
        }

        if (! ($laboratoryGroup instanceof LaboratoryGroup)) {
            $laboratoryGroup = LaboratoryGroup::findOrFail($id);
        }

        $this->authorize('delete', $laboratoryGroup);

        $laboratoryGroup->delete();

        return response()->noContent();
    }
}
