<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRadiologyItemRequest;
use App\Http\Requests\UpdateRadiologyItemRequest;
use App\Http\Resources\RadiologyItemResource;
use App\Models\Legacy\LegacyRadiologyItem;
use App\Models\MasterData\RadiologyItem;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RadiologyItemController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        if (LegacyAccess::isAvailable('ref_item_rad')) {
            $query = LegacyRadiologyItem::query()->with('group')->orderBy('nama', 'asc');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('radiology_item_group_id')) {
                $query->where('id_kelompok_item_rad', $request->radiology_item_group_id);
            }

            if ($request->has('is_active') && $request->input('is_active') !== '' && $request->input('is_active') !== null) {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            return RadiologyItemResource::collection($query->paginate($perPage));
        }

        $query = RadiologyItem::query()->with('group');

        if ($request->has('search') && trim($request->input('search')) !== '') {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('group', function ($g) use ($search) {
                        $g->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('radiology_item_group_id') && $request->radiology_item_group_id !== '') {
            $query->where('radiology_item_group_id', $request->radiology_item_group_id);
        }

        if ($request->has('is_active') && $request->input('is_active') !== '' && $request->input('is_active') !== null) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = $request->input('per_page', 10);
        $sortBy = $request->get('sortBy') ?? $request->get('sort_by', 'name');
        $sortDesc = filter_var($request->get('sortDesc', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var($request->get('sort_desc', false), FILTER_VALIDATE_BOOLEAN)
            || strtolower($request->get('direction', 'asc')) === 'desc';

        $allowedSorts = ['id', 'name', 'radiology_item_group_id', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'name';
        }

        $items = $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc')->paginate($perPage);

        return RadiologyItemResource::collection($items);
    }

    public function store(StoreRadiologyItemRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_item_rad')) {
            $data = $request->validated();
            $item = LegacyRadiologyItem::create([
                'nama' => $data['name'],
                'id_kelompok_item_rad' => $data['radiology_item_group_id'],
                'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? 1 : 0) : 1,
                'id_user' => auth()->id() ?? 1,
            ]);

            return (new RadiologyItemResource($item->load('group')))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        }

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
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($radiologyItem)
    {
        $id = $radiologyItem instanceof LegacyRadiologyItem || $radiologyItem instanceof RadiologyItem
            ? $radiologyItem->id
            : $radiologyItem;

        if (LegacyAccess::isAvailable('ref_item_rad')) {
            $legacy = LegacyRadiologyItem::with('group')->findOrFail($id);
            return new RadiologyItemResource($legacy);
        }

        if (! ($radiologyItem instanceof RadiologyItem)) {
            $radiologyItem = RadiologyItem::findOrFail($id);
        }

        $radiologyItem->load('group');

        return new RadiologyItemResource($radiologyItem);
    }

    public function update(UpdateRadiologyItemRequest $request, $radiologyItem)
    {
        $id = $radiologyItem instanceof LegacyRadiologyItem || $radiologyItem instanceof RadiologyItem
            ? $radiologyItem->id
            : $radiologyItem;

        if (LegacyAccess::isAvailable('ref_item_rad')) {
            $legacy = LegacyRadiologyItem::findOrFail($id);
            $data = $request->validated();
            $updateData = [];
            if (array_key_exists('name', $data)) {
                $updateData['nama'] = $data['name'];
            }
            if (array_key_exists('radiology_item_group_id', $data)) {
                $updateData['id_kelompok_item_rad'] = $data['radiology_item_group_id'];
            }
            if (array_key_exists('is_active', $data)) {
                $updateData['status'] = $data['is_active'] ? 1 : 0;
            }
            $legacy->update($updateData);

            return new RadiologyItemResource($legacy->load('group'));
        }

        if (! ($radiologyItem instanceof RadiologyItem)) {
            $radiologyItem = RadiologyItem::findOrFail($id);
        }

        try {
            DB::beginTransaction();
            $radiologyItem->update($request->validated());
            DB::commit();

            return new RadiologyItemResource($radiologyItem);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update radiology item',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($radiologyItem)
    {
        $id = $radiologyItem instanceof LegacyRadiologyItem || $radiologyItem instanceof RadiologyItem
            ? $radiologyItem->id
            : $radiologyItem;

        if (LegacyAccess::isAvailable('ref_item_rad')) {
            $legacy = LegacyRadiologyItem::findOrFail($id);
            $legacy->delete();

            return response()->json(null, Response::HTTP_NO_CONTENT);
        }

        if (! ($radiologyItem instanceof RadiologyItem)) {
            $radiologyItem = RadiologyItem::findOrFail($id);
        }

        try {
            DB::beginTransaction();
            $radiologyItem->delete();
            DB::commit();

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete radiology item',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateStatus(Request $request, $radiologyItem)
    {
        $request->validate(['is_active' => 'required|boolean']);

        $id = $radiologyItem instanceof LegacyRadiologyItem || $radiologyItem instanceof RadiologyItem
            ? $radiologyItem->id
            : $radiologyItem;

        if (LegacyAccess::isAvailable('ref_item_rad')) {
            $legacy = LegacyRadiologyItem::findOrFail($id);
            $legacy->update(['status' => $request->boolean('is_active') ? 1 : 0]);

            return response()->json([
                'message' => 'Status updated successfully',
                'data' => new RadiologyItemResource($legacy->load('group')),
            ]);
        }

        if (! ($radiologyItem instanceof RadiologyItem)) {
            $radiologyItem = RadiologyItem::findOrFail($id);
        }

        try {
            DB::beginTransaction();
            $radiologyItem->update(['is_active' => $request->is_active]);
            DB::commit();

            return response()->json([
                'message' => 'Status updated successfully',
                'data' => new RadiologyItemResource($radiologyItem),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update radiology item status',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
