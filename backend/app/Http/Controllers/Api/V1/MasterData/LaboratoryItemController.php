<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreLaboratoryItemRequest;
use App\Http\Requests\MasterData\UpdateLaboratoryItemRequest;
use App\Http\Resources\MasterData\LaboratoryItemResource;
use App\Models\MasterData\LaboratoryItem;
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

        $item = LaboratoryItem::create($request->validated());

        return response()->json([
            'message' => 'Item lab berhasil ditambahkan.',
            'data' => new LaboratoryItemResource($item),
        ], 201);
    }

    public function show(LaboratoryItem $laboratoryItem): LaboratoryItemResource
    {
        $this->authorize('view', $laboratoryItem);

        return new LaboratoryItemResource($laboratoryItem);
    }

    public function update(UpdateLaboratoryItemRequest $request, LaboratoryItem $laboratoryItem): JsonResponse
    {
        $this->authorize('update', $laboratoryItem);

        $laboratoryItem->update($request->validated());

        return response()->json([
            'message' => 'Item lab berhasil diperbarui.',
            'data' => new LaboratoryItemResource($laboratoryItem->refresh()),
        ]);
    }

    public function destroy(LaboratoryItem $laboratoryItem): JsonResponse
    {
        $this->authorize('delete', $laboratoryItem);

        $laboratoryItem->delete();

        return response()->json(['message' => 'Item lab berhasil diarsipkan.']);
    }
}
