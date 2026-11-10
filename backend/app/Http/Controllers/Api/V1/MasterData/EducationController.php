<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreEducationRequest;
use App\Http\Requests\MasterData\UpdateEducationRequest;
use App\Http\Resources\MasterData\EducationResource;
use App\Models\Education;
use App\Models\Employee;
use App\Models\Legacy\LegacyEducation;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EducationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);

        if (LegacyAccess::isAvailable('ref_pendidikan')) {
            $query = LegacyEducation::query();

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $sortBy = $request->get('sort_by', 'id');
            $sortDesc = $request->boolean('sort_desc', false);
            $dbSort = $sortBy === 'name' ? 'pendidikan' : $sortBy;

            $allowedSorts = ['id', 'pendidikan', 'status'];
            if (in_array($dbSort, $allowedSorts)) {
                $query->orderBy($dbSort, $sortDesc ? 'desc' : 'asc');
            }

            return EducationResource::collection($query->paginate($perPage));
        }

        $query = Education::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $sortBy = $request->get('sort_by', 'id');
        $sortDesc = $request->boolean('sort_desc', false);

        $allowedSorts = ['id', 'name', 'is_active'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        }

        $educations = $query->paginate($perPage);

        return EducationResource::collection($educations);
    }

    public function store(StoreEducationRequest $request)
    {
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_pendidikan')) {
            $exists = LegacyEducation::whereRaw('LOWER(pendidikan) = ?', [strtolower($data['name'])])->exists();
            if ($exists) {
                return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pendidikan sudah digunakan.']]], 422);
            }

            $legacy = DB::connection('legacy')->transaction(function () use ($data) {
                return LegacyEducation::create([
                    'pendidikan' => $data['name'],
                    'status' => array_key_exists('is_active', $data) ? ($data['is_active'] ? '1' : '0') : '1',
                ]);
            });

            return (new EducationResource($legacy))->response()->setStatusCode(201);
        }

        $exists = Education::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->exists();
        if ($exists) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pendidikan sudah digunakan.']]], 422);
        }

        $education = Education::create($data);

        return new EducationResource($education);
    }

    public function show($education)
    {
        $id = $education instanceof Education || $education instanceof LegacyEducation ? $education->id : $education;
        if (LegacyAccess::isAvailable('ref_pendidikan')) {
            return new EducationResource(LegacyEducation::findOrFail($id));
        }

        if (! ($education instanceof Education)) {
            $education = Education::findOrFail($id);
        }

        return new EducationResource($education);
    }

    public function update(UpdateEducationRequest $request, $education)
    {
        $id = $education instanceof Education || $education instanceof LegacyEducation ? $education->id : $education;
        $data = $request->validated();

        if (LegacyAccess::isAvailable('ref_pendidikan')) {
            $legacy = LegacyEducation::findOrFail($id);

            $exists = LegacyEducation::whereRaw('LOWER(pendidikan) = ?', [strtolower($data['name'])])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pendidikan sudah digunakan.']]], 422);
            }

            DB::connection('legacy')->transaction(function () use ($legacy, $data) {
                $updateData = ['pendidikan' => $data['name']];
                if (array_key_exists('is_active', $data)) {
                    $updateData['status'] = $data['is_active'] ? '1' : '0';
                }
                $legacy->update($updateData);
            });

            return new EducationResource($legacy->fresh());
        }

        if (! ($education instanceof Education)) {
            $education = Education::findOrFail($id);
        }

        $exists = Education::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->where('id', '!=', $education->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pendidikan sudah digunakan.']]], 422);
        }

        $education->update($data);

        return new EducationResource($education);
    }

    public function destroy($education)
    {
        $id = $education instanceof Education || $education instanceof LegacyEducation ? $education->id : $education;

        if (LegacyAccess::isAvailable('ref_pendidikan')) {
            $legacy = LegacyEducation::findOrFail($id);

            $isUsed = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('master_person_index')) {
                $isUsed = DB::connection('legacy')->table('master_person_index')
                    ->where('id_pendidikan', $id)
                    ->exists();
            }

            if ($isUsed) {
                return response()->json(['message' => 'Pendidikan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.'], 409);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($education instanceof Education)) {
            $education = Education::findOrFail($id);
        }

        // Check if used by any Employee
        $isUsed = Employee::where('education_id', $education->id)->exists();

        if ($isUsed) {
            return response()->json(['message' => 'Pendidikan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.'], 409);
        }

        $education->delete();

        return response()->noContent();
    }
}
