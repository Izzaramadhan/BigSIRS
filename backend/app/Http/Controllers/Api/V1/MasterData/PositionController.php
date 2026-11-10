<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StorePositionRequest;
use App\Http\Requests\MasterData\UpdatePositionRequest;
use App\Http\Resources\MasterData\PositionResource;
use App\Models\Legacy\LegacyPosition;
use App\Models\MasterData\Position;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);

        if (LegacyAccess::isAvailable('ref_jabatan')) {
            $query = LegacyPosition::query()->orderBy('jabatan');

            if ($request->filled('search')) {
                $query->search($request->input('search'));
            }

            return PositionResource::collection($query->paginate($perPage));
        }

        $query = Position::query();

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $positions = $query->orderBy('name')->paginate($perPage);

        return PositionResource::collection($positions);
    }

    public function store(StorePositionRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_jabatan')) {
            $data = $request->validated();
            $legacy = DB::connection('legacy')->transaction(function () use ($data) {
                return LegacyPosition::create([
                    'jabatan' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'kode' => $data['code'] ?? null,
                    'status' => '1',
                ]);
            });

            return response()->json([
                'message' => 'Data jabatan berhasil ditambahkan',
                'data' => new PositionResource($legacy),
            ], Response::HTTP_CREATED);
        }

        $position = Position::create($request->validated());

        return response()->json([
            'message' => 'Data jabatan berhasil ditambahkan',
            'data' => new PositionResource($position),
        ], Response::HTTP_CREATED);
    }

    public function show($position)
    {
        $id = $position instanceof Position || $position instanceof LegacyPosition ? $position->id : $position;
        if (LegacyAccess::isAvailable('ref_jabatan')) {
            $legacy = LegacyPosition::findOrFail($id);
            return new PositionResource($legacy);
        }

        if (! ($position instanceof Position)) {
            $position = Position::findOrFail($id);
        }

        return new PositionResource($position);
    }

    public function update(UpdatePositionRequest $request, $position)
    {
        $id = $position instanceof Position || $position instanceof LegacyPosition ? $position->id : $position;
        if (LegacyAccess::isAvailable('ref_jabatan')) {
            $legacy = LegacyPosition::findOrFail($id);
            $data = $request->validated();

            DB::connection('legacy')->transaction(function () use ($legacy, $data) {
                $updateData = ['jabatan' => $data['name']];
                if (array_key_exists('description', $data)) {
                    $updateData['deskripsi'] = $data['description'];
                }
                if (array_key_exists('code', $data)) {
                    $updateData['kode'] = $data['code'];
                }
                $legacy->update($updateData);
            });

            return response()->json([
                'message' => 'Data jabatan berhasil diperbarui',
                'data' => new PositionResource($legacy->fresh()),
            ]);
        }

        if (! ($position instanceof Position)) {
            $position = Position::findOrFail($id);
        }

        $position->update($request->validated());

        return response()->json([
            'message' => 'Data jabatan berhasil diperbarui',
            'data' => new PositionResource($position),
        ]);
    }

    public function destroy($position)
    {
        $id = $position instanceof Position || $position instanceof LegacyPosition ? $position->id : $position;
        if (LegacyAccess::isAvailable('ref_jabatan')) {
            $legacy = LegacyPosition::findOrFail($id);

            $isUsed = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('ref_pegawai')) {
                $isUsed = DB::connection('legacy')->table('ref_pegawai')
                    ->where('id_jabatan', $id)
                    ->exists();
            }

            if ($isUsed) {
                return response()->json([
                    'message' => 'Data jabatan tidak dapat dihapus karena sedang digunakan oleh data pegawai.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->performSoftDelete(auth()->id() ?? request()->user()?->getLegacyUserId());
            });

            return response()->json([
                'message' => 'Data jabatan berhasil dihapus',
            ]);
        }

        if (! ($position instanceof Position)) {
            $position = Position::findOrFail($id);
        }

        if ($position->employees()->exists()) {
            return response()->json([
                'message' => 'Data jabatan tidak dapat dihapus karena sedang digunakan oleh data pegawai.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $position->delete();

        return response()->json([
            'message' => 'Data jabatan berhasil dihapus',
        ]);
    }
}
