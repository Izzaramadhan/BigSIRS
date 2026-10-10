<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreOccupationRequest;
use App\Http\Requests\MasterData\UpdateOccupationRequest;
use App\Http\Resources\MasterData\OccupationResource;
use App\Models\Employee;
use App\Models\Occupation;
use Illuminate\Http\Request;

class OccupationController extends Controller
{
    public function index(Request $request)
    {
        $query = Occupation::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }



        $sortBy = $request->get('sort_by', 'id');
        $sortDesc = $request->boolean('sort_desc', false);

        $allowedSorts = ['id', 'name'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        }

        $perPage = $request->get('per_page', 10);
        $occupations = $query->paginate($perPage);

        return OccupationResource::collection($occupations);
    }

    public function store(StoreOccupationRequest $request)
    {
        $data = $request->validated();

        $exists = Occupation::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->exists();
        if ($exists) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pekerjaan sudah digunakan.']]], 422);
        }

        $occupation = Occupation::create($data);

        return new OccupationResource($occupation);
    }

    public function show(Occupation $occupation)
    {
        return new OccupationResource($occupation);
    }

    public function update(UpdateOccupationRequest $request, Occupation $occupation)
    {
        $data = $request->validated();

        $exists = Occupation::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->where('id', '!=', $occupation->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pekerjaan sudah digunakan.']]], 422);
        }

        $occupation->update($data);

        return new OccupationResource($occupation);
    }

    public function destroy(Occupation $occupation)
    {
        // Check if used by any Employee
        $isUsed = Employee::where('occupation_id', $occupation->id)->exists();

        if ($isUsed) {
            return response()->json(['message' => 'Pekerjaan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.'], 409);
        }

        $occupation->delete();

        return response()->noContent();
    }


}
