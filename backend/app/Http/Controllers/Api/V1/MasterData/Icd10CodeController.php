<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MasterData\Icd10CodeResource;
use App\Models\MasterData\Icd10Code;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class Icd10CodeController extends Controller
{
    public function index(Request $request)
    {
        $query = Icd10Code::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('english_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sortField = $request->get('sort_by', 'code');
        $sortDir = $request->get('sort_dir', 'asc');
        $allowedSorts = ['id', 'code', 'name', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        }

        $perPage = (int) $request->get('per_page', 10);
        $data = $query->paginate($perPage);

        return Icd10CodeResource::collection($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:icd10_codes,code'],
            'name' => 'required|string|max:255',
            'english_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_medical_history' => 'boolean',
            'is_active' => 'boolean',
            'inacbg_code' => 'nullable|string|max:100',
            'inacbg_name' => 'nullable|string',
            'class_1_tariff' => 'nullable|numeric|min:0',
            'class_2_tariff' => 'nullable|numeric|min:0',
            'class_3_tariff' => 'nullable|numeric|min:0',
        ]);

        $validated['code'] = trim(strtoupper($validated['code']));

        $icd10 = Icd10Code::create($validated);

        return new Icd10CodeResource($icd10);
    }

    public function show(string $id)
    {
        $icd10 = Icd10Code::findOrFail($id);

        return new Icd10CodeResource($icd10);
    }

    public function update(Request $request, string $id)
    {
        $icd10 = Icd10Code::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('icd10_codes')->ignore($icd10->id)],
            'name' => 'required|string|max:255',
            'english_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_medical_history' => 'boolean',
            'is_active' => 'boolean',
            'inacbg_code' => 'nullable|string|max:100',
            'inacbg_name' => 'nullable|string',
            'class_1_tariff' => 'nullable|numeric|min:0',
            'class_2_tariff' => 'nullable|numeric|min:0',
            'class_3_tariff' => 'nullable|numeric|min:0',
        ]);

        $validated['code'] = trim(strtoupper($validated['code']));

        $icd10->update($validated);

        return new Icd10CodeResource($icd10);
    }

    public function destroy(string $id)
    {
        $icd10 = Icd10Code::findOrFail($id);
        // Note: As per instructions, "Delete tidak boleh menghapus transaksi diagnosis pasien."
        // We will just use soft delete because it is a master data reference.
        $icd10->delete();

        return response()->json(['message' => 'ICD-10 berhasil dihapus']);
    }

    public function updateStatus(Request $request, string $id)
    {
        $icd10 = Icd10Code::findOrFail($id);
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $icd10->update($validated);

        return new Icd10CodeResource($icd10);
    }
}
