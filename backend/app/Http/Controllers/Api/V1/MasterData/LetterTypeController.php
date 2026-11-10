<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterData\StoreLetterTypeRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateLetterTypeRequest;
use App\Http\Resources\Api\V1\MasterData\LetterTypeResource;
use App\Models\Legacy\LegacyLetterType;
use App\Models\LetterType;
use App\Services\MasterData\LetterTypeService;
use App\Support\LegacyAccess;
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
        $this->authorize('viewAny', LegacyAccess::isAvailable('ref_jenis_surat') ? LegacyLetterType::class : LetterType::class);
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

    public function show($letterType): LetterTypeResource
    {
        $id = $letterType instanceof LegacyLetterType || $letterType instanceof LetterType ? $letterType->id : $letterType;
        if (LegacyAccess::isAvailable('ref_jenis_surat')) {
            $legacy = LegacyLetterType::findOrFail($id);
            $this->authorize('view', $legacy);
            return new LetterTypeResource($legacy);
        }

        if (! ($letterType instanceof LetterType)) {
            $letterType = LetterType::findOrFail($id);
        }

        $this->authorize('view', $letterType);

        return new LetterTypeResource($letterType);
    }

    public function update(UpdateLetterTypeRequest $request, $letterType): JsonResponse
    {
        $letterType = $this->letterTypeService->update($letterType, $request->validated());

        return response()->json([
            'message' => 'Surat berhasil diperbarui',
            'data' => new LetterTypeResource($letterType),
        ]);
    }

    public function updateStatus(Request $request, $letterType): JsonResponse
    {
        $id = $letterType instanceof LegacyLetterType || $letterType instanceof LetterType ? $letterType->id : $letterType;
        $target = LegacyAccess::isAvailable('ref_jenis_surat') ? LegacyLetterType::findOrFail($id) : ($letterType instanceof LetterType ? $letterType : LetterType::findOrFail($id));

        $this->authorize('update', $target);
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $updated = $this->letterTypeService->updateStatus($target, filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));

        return response()->json([
            'message' => 'Status surat berhasil diperbarui',
            'data' => new LetterTypeResource($updated),
        ]);
    }

    public function destroy($letterType): JsonResponse
    {
        $id = $letterType instanceof LegacyLetterType || $letterType instanceof LetterType ? $letterType->id : $letterType;
        $target = LegacyAccess::isAvailable('ref_jenis_surat') ? LegacyLetterType::findOrFail($id) : ($letterType instanceof LetterType ? $letterType : LetterType::findOrFail($id));

        $this->authorize('delete', $target);
        $this->letterTypeService->delete($target);

        return response()->json([
            'message' => 'Surat berhasil dihapus',
        ]);
    }
}
