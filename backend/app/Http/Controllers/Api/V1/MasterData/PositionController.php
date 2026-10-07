<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StorePositionRequest;
use App\Http\Requests\MasterData\UpdatePositionRequest;
use App\Http\Resources\MasterData\PositionResource;
use App\Models\MasterData\Position;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $query = Position::query();

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }


        $perPage = $request->get('per_page', 10);
        $positions = $query->orderBy('name')->paginate($perPage);

        return PositionResource::collection($positions);
    }

    public function store(StorePositionRequest $request)
    {
        $position = Position::create($request->validated());

        return response()->json([
            'message' => 'Data jabatan berhasil ditambahkan',
            'data' => new PositionResource($position)
        ], Response::HTTP_CREATED);
    }

    public function show(Position $position)
    {
        return new PositionResource($position);
    }

    public function update(UpdatePositionRequest $request, Position $position)
    {
        $position->update($request->validated());

        return response()->json([
            'message' => 'Data jabatan berhasil diperbarui',
            'data' => new PositionResource($position)
        ]);
    }


    public function destroy(Position $position)
    {
        if ($position->employees()->exists()) {
            return response()->json([
                'message' => 'Data jabatan tidak dapat dihapus karena sedang digunakan oleh data pegawai.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $position->delete();

        return response()->json([
            'message' => 'Data jabatan berhasil dihapus'
        ]);
    }
}
