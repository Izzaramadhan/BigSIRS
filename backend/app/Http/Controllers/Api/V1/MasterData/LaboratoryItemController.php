<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreLaboratoryItemRequest;
use App\Http\Requests\MasterData\UpdateLaboratoryItemRequest;
use App\Http\Resources\MasterData\LaboratoryItemResource;
use App\Models\Legacy\LegacyLaboratoryItem;
use App\Models\MasterData\LaboratoryItem;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LaboratoryItemController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', LaboratoryItem::class);

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        if (LegacyAccess::isAvailable('ref_item_lab')) {
            $query = LegacyLaboratoryItem::query()->orderBy('nama', 'asc');

            if ($request->filled('search')) {
                $query->search($request->string('search')->trim()->toString());
            }

            if ($request->has('is_active')) {
                $isActive = $request->boolean('is_active');
                $query->where('status', $isActive ? 1 : 0);
            }

            return LaboratoryItemResource::collection($query->paginate($perPage));
        }

        $query = LaboratoryItem::query()->search($request->string('search')->trim()->toString());

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sortBy = in_array($request->input('sort_by'), ['name', 'reference_value', 'unit', 'is_active'], true)
            ? $request->input('sort_by')
            : 'name';
        $sortDirection = $request->input('sort_direction') === 'desc' ? 'desc' : 'asc';

        return LaboratoryItemResource::collection(
            $query->orderBy($sortBy, $sortDirection)->orderBy('id')->paginate($perPage)
        );
    }

    public function store(StoreLaboratoryItemRequest $request): JsonResponse
    {
        $this->authorize('create', LaboratoryItem::class);

        if (LegacyAccess::isAvailable('ref_item_lab')) {
            $data = $request->validated();
            $item = LegacyLaboratoryItem::create([
                'nama' => $data['name'],
                'standar_normal' => $data['reference_value'] ?? null,
                'satuan' => $data['unit'] ?? null,
                'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? '1' : '0') : '1',
                'id_user' => auth()->id() ?? 1,
            ]);

            return response()->json([
                'message' => 'Item lab berhasil ditambahkan.',
                'data' => new LaboratoryItemResource($item),
            ], 201);
        }

        $item = LaboratoryItem::create($request->validated());

        return response()->json([
            'message' => 'Item lab berhasil ditambahkan.',
            'data' => new LaboratoryItemResource($item),
        ], 201);
    }

    public function show($laboratoryItem): LaboratoryItemResource
    {
        $id = $laboratoryItem instanceof LegacyLaboratoryItem || $laboratoryItem instanceof LaboratoryItem
            ? $laboratoryItem->id
            : $laboratoryItem;

        if (LegacyAccess::isAvailable('ref_item_lab')) {
            $legacy = LegacyLaboratoryItem::findOrFail($id);
            return new LaboratoryItemResource($legacy);
        }

        if (! ($laboratoryItem instanceof LaboratoryItem)) {
            $laboratoryItem = LaboratoryItem::findOrFail($id);
        }

        $this->authorize('view', $laboratoryItem);

        return new LaboratoryItemResource($laboratoryItem);
    }

    public function update(UpdateLaboratoryItemRequest $request, $laboratoryItem): JsonResponse
    {
        $id = $laboratoryItem instanceof LegacyLaboratoryItem || $laboratoryItem instanceof LaboratoryItem
            ? $laboratoryItem->id
            : $laboratoryItem;

        if (LegacyAccess::isAvailable('ref_item_lab')) {
            $legacy = LegacyLaboratoryItem::findOrFail($id);
            $this->authorize('update', $legacy);

            $data = $request->validated();
            $updateData = [
                'nama' => $data['name'],
                'standar_normal' => $data['reference_value'] ?? null,
                'satuan' => $data['unit'] ?? null,
            ];
            if (array_key_exists('is_active', $data)) {
                $updateData['status'] = $data['is_active'] ? '1' : '0';
            }
            $legacy->update($updateData);

            return response()->json([
                'message' => 'Item lab berhasil diperbarui.',
                'data' => new LaboratoryItemResource($legacy->refresh()),
            ]);
        }

        if (! ($laboratoryItem instanceof LaboratoryItem)) {
            $laboratoryItem = LaboratoryItem::findOrFail($id);
        }

        $this->authorize('update', $laboratoryItem);

        $laboratoryItem->update($request->validated());

        return response()->json([
            'message' => 'Item lab berhasil diperbarui.',
            'data' => new LaboratoryItemResource($laboratoryItem->refresh()),
        ]);
    }

    public function destroy($laboratoryItem): JsonResponse
    {
        $id = $laboratoryItem instanceof LegacyLaboratoryItem || $laboratoryItem instanceof LaboratoryItem
            ? $laboratoryItem->id
            : $laboratoryItem;

        if (LegacyAccess::isAvailable('ref_item_lab')) {
            $legacy = LegacyLaboratoryItem::findOrFail($id);
            $this->authorize('delete', $legacy);

            $legacy->delete();

            return response()->json(['message' => 'Item lab berhasil diarsipkan.']);
        }

        if (! ($laboratoryItem instanceof LaboratoryItem)) {
            $laboratoryItem = LaboratoryItem::findOrFail($id);
        }

        $this->authorize('delete', $laboratoryItem);

        $laboratoryItem->delete();

        return response()->json(['message' => 'Item lab berhasil diarsipkan.']);
    }
}
