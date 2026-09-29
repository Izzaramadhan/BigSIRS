<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Models\MasterData\ReportGroup;
use Illuminate\Http\Request;

class ReportGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = ReportGroup::query();

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active === 'true' || $request->is_active === '1');
        }

        return response()->json([
            'data' => $query->limit(100)->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:30',
            'is_active' => 'boolean'
        ]);

        $rg = ReportGroup::create($data);
        return response()->json(['data' => $rg], 201);
    }

    public function show(ReportGroup $reportGroup)
    {
        return response()->json(['data' => $reportGroup]);
    }

    public function update(Request $request, ReportGroup $reportGroup)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:30',
            'is_active' => 'boolean'
        ]);

        $reportGroup->update($data);
        return response()->json(['data' => $reportGroup]);
    }

    public function updateStatus(Request $request, ReportGroup $reportGroup)
    {
        $data = $request->validate([
            'is_active' => 'required|boolean'
        ]);
        $reportGroup->update($data);
        return response()->json(['data' => $reportGroup]);
    }

    public function destroy(ReportGroup $reportGroup)
    {
        $reportGroup->delete();
        return response()->noContent();
    }
}
