<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreOccupationRequest;
use App\Http\Requests\MasterData\UpdateOccupationRequest;
use App\Http\Resources\MasterData\OccupationResource;
use App\Models\Employee;
use App\Models\Legacy\LegacyOccupation;
use App\Models\Occupation;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OccupationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);

        if (LegacyAccess::isAvailable('ref_pekerjaan')) {
            $query = LegacyOccupation::query();

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $sortBy = $request->get('sort_by', 'id');
            $sortDesc = $request->boolean('sort_desc', false);
            $dbSort = $sortBy === 'name' ? 'pekerjaan' : $sortBy;

            $allowedSorts = ['id', 'pekerjaan', 'status'];
            if (in_array($dbSort, $allowedSorts)) {
                $query->orderBy($dbSort, $sortDesc ? 'desc' : 'asc');
            }

            return OccupationResource::collection($query->paginate($perPage));
        }

        $query = Occupation::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $sortBy = $request->get('sort_by', 'id');
        $sortDesc = $request->boolean('sort_desc', false);

        $allowedSorts = ['id', 'name'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        }

        $occupations = $query->paginate($perPage);

        return OccupationResource::collection($occupations);
    }

    public function store(StoreOccupationRequest $request)
    {
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_pekerjaan')) {
            $exists = LegacyOccupation::whereRaw('LOWER(pekerjaan) = ?', [strtolower($data['name'])])->exists();
            if ($exists) {
                return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pekerjaan sudah digunakan.']]], 422);
            }

            $legacy = DB::connection('legacy')->transaction(function () use ($data) {
                return LegacyOccupation::create([
                    'pekerjaan' => $data['name'],
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? '1' : '0') : '1',
                ]);
            });

            return (new OccupationResource($legacy))->response()->setStatusCode(201);
        }

        $exists = Occupation::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->exists();
        if ($exists) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pekerjaan sudah digunakan.']]], 422);
        }

        $occupation = Occupation::create($data);

        return new OccupationResource($occupation);
    }

    public function show($occupation)
    {
        $id = $occupation instanceof Occupation || $occupation instanceof LegacyOccupation ? $occupation->id : $occupation;
        if (LegacyAccess::isAvailable('ref_pekerjaan')) {
            return new OccupationResource(LegacyOccupation::findOrFail($id));
        }

        if (! ($occupation instanceof Occupation)) {
            $occupation = Occupation::findOrFail($id);
        }

        return new OccupationResource($occupation);
    }

    public function update(UpdateOccupationRequest $request, $occupation)
    {
        $id = $occupation instanceof Occupation || $occupation instanceof LegacyOccupation ? $occupation->id : $occupation;
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_pekerjaan')) {
            $legacy = LegacyOccupation::findOrFail($id);

            $exists = LegacyOccupation::whereRaw('LOWER(pekerjaan) = ?', [strtolower($data['name'])])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pekerjaan sudah digunakan.']]], 422);
            }

            DB::connection('legacy')->transaction(function () use ($legacy, $data) {
                $updateData = ['pekerjaan' => $data['name']];
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? '1' : '0';
                }
                $legacy->update($updateData);
            });

            return new OccupationResource($legacy->fresh());
        }

        if (! ($occupation instanceof Occupation)) {
            $occupation = Occupation::findOrFail($id);
        }

        $exists = Occupation::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->where('id', '!=', $occupation->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pekerjaan sudah digunakan.']]], 422);
        }

        $occupation->update($data);

        return new OccupationResource($occupation);
    }

    public function destroy($occupation)
    {
        $id = $occupation instanceof Occupation || $occupation instanceof LegacyOccupation ? $occupation->id : $occupation;

        if (LegacyAccess::isAvailable('ref_pekerjaan')) {
            $legacy = LegacyOccupation::findOrFail($id);

            $isUsed = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('master_person_index')) {
                $isUsed = DB::connection('legacy')->table('master_person_index')
                    ->where('id_pekerjaan', $id)
                    ->exists();
            }

            if ($isUsed) {
                return response()->json(['message' => 'Pekerjaan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.'], 409);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($occupation instanceof Occupation)) {
            $occupation = Occupation::findOrFail($id);
        }

        // Check if used by any Employee
        $isUsed = Employee::where('occupation_id', $occupation->id)->exists();

        if ($isUsed) {
            return response()->json(['message' => 'Pekerjaan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.'], 409);
        }

        $occupation->delete();

        return response()->noContent();
    }
}
