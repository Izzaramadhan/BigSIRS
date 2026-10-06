<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterData\StoreIcd9cmCodeRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateIcd9cmCodeRequest;
use App\Http\Resources\Api\V1\MasterData\Icd9CmResource;
use App\Models\MasterData\Icd9Cm;
use Illuminate\Http\Request;

class Icd9CmController extends Controller
{
    public function index(Request $request)
    {
        $query = Icd9Cm::query();

        if ($request->has('search') && $request->search !== '') {
            $query->search($request->search);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sortField = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSorts = ['code', 'name', 'english_name', 'created_at', 'updated_at', 'is_active'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->input('per_page', 10);
        $codes = $query->paginate($perPage);

        return Icd9CmResource::collection($codes);
    }

    public function store(StoreIcd9cmCodeRequest $request)
    {
        $code = Icd9Cm::create($request->validated());

        return new Icd9CmResource($code);
    }

    public function show(Icd9Cm $icd9Cm)
    {
        return new Icd9CmResource($icd9Cm);
    }

    public function update(UpdateIcd9cmCodeRequest $request, Icd9Cm $icd9Cm)
    {
        $icd9Cm->update($request->validated());

        return new Icd9CmResource($icd9Cm);
    }

    public function destroy(Icd9Cm $icd9Cm)
    {
        $icd9Cm->delete();

        return response()->noContent();
    }

    public function updateStatus(Request $request, Icd9Cm $icd9Cm)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $icd9Cm->update(['is_active' => $request->boolean('is_active')]);

        return new Icd9CmResource($icd9Cm);
    }

    public function lookup(Request $request)
    {
        $query = Icd9Cm::query()->active();

        if ($request->has('search') && $request->search !== '') {
            $query->search($request->search);
        }

        // Always include the currently selected one, even if inactive
        if ($request->has('selected_id')) {
            $query->orWhere('id', $request->selected_id);
        }

        $codes = $query->orderBy('name', 'asc')
            ->limit(50)
            ->get(['id', 'code', 'name', 'is_active']);

        return response()->json([
            'data' => $codes,
        ]);
    }
}
