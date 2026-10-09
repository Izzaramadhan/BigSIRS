<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterData\StoreLetterTypeRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateLetterTypeRequest;
use App\Http\Resources\Api\V1\MasterData\LetterTypeResource;
use App\Models\LetterType;
use App\Services\MasterData\LetterTypeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LetterTypeController extends Controller
{
    use AuthorizesRequests;

    protected LetterTypeService $letterTypeService;

    public function __construct(LetterTypeService $letterTypeService)
    {
        $this->letterTypeService = $letterTypeService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', LetterType::class);
        $perPage = (int) $request->input('per_page', 10);
        $filters = $request->only(['search', 'is_active', 'sort_by', 'sort_dir']);

        $letterTypes = $this->letterTypeService->getPaginated($filters, $perPage);

        return LetterTypeResource::collection($letterTypes);
    }

    public function store(StoreLetterTypeRequest $request): JsonResponse
    {
        $letterType = $this->letterTypeService->create($request->validated());

        return response()->json([
            'message' => 'Surat berhasil ditambahkan',
            'data' => new LetterTypeResource($letterType),
        ], 201);
    }

    public function show(LetterType $letterType): LetterTypeResource
    {
        $this->authorize('view', $letterType);

        return new LetterTypeResource($letterType);
    }

    public function update(UpdateLetterTypeRequest $request, LetterType $letterType): JsonResponse
    {
        $letterType = $this->letterTypeService->update($letterType, $request->validated());

        return response()->json([
            'message' => 'Surat berhasil diperbarui',
            'data' => new LetterTypeResource($letterType),
        ]);
    }

    public function updateStatus(Request $request, LetterType $letterType): JsonResponse
    {
        $this->authorize('update', $letterType);
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $letterType = $this->letterTypeService->updateStatus($letterType, $request->is_active);

        return response()->json([
            'message' => 'Status surat berhasil diperbarui',
            'data' => new LetterTypeResource($letterType),
        ]);
    }

    public function destroy(LetterType $letterType): JsonResponse
    {
        $this->authorize('delete', $letterType);
        $this->letterTypeService->delete($letterType);

        return response()->json([
            'message' => 'Surat berhasil dihapus',
        ]);
    }
}
