<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Education;
use App\Http\Requests\MasterData\StoreEducationRequest;
use App\Http\Requests\MasterData\UpdateEducationRequest;
use App\Http\Resources\MasterData\EducationResource;
use Illuminate\Http\Request;

class EducationController extends Controller
{
    public function index(Request $request)
    {
        $query = Education::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        $sortBy = $request->get('sort_by', 'id');
        $sortDesc = $request->boolean('sort_desc', false);
        
        $allowedSorts = ['id', 'name', 'is_active'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        }

        $perPage = $request->get('per_page', 10);
        $educations = $query->paginate($perPage);

        return EducationResource::collection($educations);
    }

    public function store(StoreEducationRequest $request)
    {
        $data = $request->validated();
        
        $exists = Education::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->exists();
        if ($exists) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pendidikan sudah digunakan.']]], 422);
        }

        $education = Education::create($data);

        return new EducationResource($education);
    }

    public function show(Education $education)
    {
        return new EducationResource($education);
    }

    public function update(UpdateEducationRequest $request, Education $education)
    {
        $data = $request->validated();
        
        $exists = Education::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->where('id', '!=', $education->id)
            ->exists();
            
        if ($exists) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => ['name' => ['Nama Pendidikan sudah digunakan.']]], 422);
        }

        $education->update($data);

        return new EducationResource($education);
    }

    public function destroy(Education $education)
    {
        // Check if used by any Employee
        $isUsed = \App\Models\Employee::where('education_id', $education->id)->exists();
        
        if ($isUsed) {
            return response()->json(['message' => 'Pendidikan tidak dapat dihapus karena sedang digunakan oleh data Pegawai/Dokter.'], 409);
        }
        
        $education->delete();
        return response()->noContent();
    }

    public function updateStatus(Request $request, Education $education)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $education->update(['is_active' => $validated['is_active']]);

        return new EducationResource($education);
    }
}
