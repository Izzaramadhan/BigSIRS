<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Legacy\LegacyReportGroup;
use App\Models\MasterData\ReportGroup;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;

class ReportGroupController extends Controller
{
    public function index(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_report_group')) {
            $query = LegacyReportGroup::query();

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active') && $request->is_active !== '') {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? '1' : '0');
            }

            return response()->json([
                'data' => $query->limit(100)->get(),
            ]);
        }

        $query = ReportGroup::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
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
            'data' => $query->limit(100)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:30',
            'is_active' => 'boolean',
        ]);

        if (LegacyAccess::isAvailable('ref_report_group')) {
            $legacyUserId = $request->user()?->legacy_user_id ?? 1;
            $rg = LegacyReportGroup::create([
                'nama' => $data['name'],
                'deskripsi' => $data['description'] ?? null,
                'type' => $data['type'] ?? null,
                'tipe' => $data['type'] ?? null,
                'status' => ($data['is_active'] ?? true) ? '1' : '0',
                'id_user' => $legacyUserId,
                'created_at' => now(),
            ]);

            return response()->json(['data' => $rg], 201);
        }

        $rg = ReportGroup::create($data);

        return response()->json(['data' => $rg], 201);
    }

    public function show($reportGroup)
    {
        $id = $reportGroup instanceof ReportGroup ? $reportGroup->id : ($reportGroup instanceof LegacyReportGroup ? $reportGroup->id : $reportGroup);
        if (LegacyAccess::isAvailable('ref_report_group')) {
            $legacy = LegacyReportGroup::findOrFail($id);
            return response()->json(['data' => $legacy]);
        }

        $rg = $reportGroup instanceof ReportGroup ? $reportGroup : ReportGroup::findOrFail($id);
        return response()->json(['data' => $rg]);
    }

    public function update(Request $request, $reportGroup)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:30',
            'is_active' => 'boolean',
        ]);

        $id = $reportGroup instanceof ReportGroup ? $reportGroup->id : ($reportGroup instanceof LegacyReportGroup ? $reportGroup->id : $reportGroup);

        if (LegacyAccess::isAvailable('ref_report_group')) {
            $legacy = LegacyReportGroup::findOrFail($id);
            $legacyUserId = $request->user()?->legacy_user_id ?? 1;
            $legacy->update([
                'nama' => $data['name'],
                'deskripsi' => $data['description'] ?? null,
                'type' => $data['type'] ?? null,
                'tipe' => $data['type'] ?? null,
                'status' => isset($data['is_active']) ? ($data['is_active'] ? '1' : '0') : $legacy->status,
                'id_user' => $legacyUserId,
                'updated_at' => now(),
            ]);

            return response()->json(['data' => $legacy->fresh()]);
        }

        $rg = $reportGroup instanceof ReportGroup ? $reportGroup : ReportGroup::findOrFail($id);
        $rg->update($data);

        return response()->json(['data' => $rg]);
    }

    public function updateStatus(Request $request, $reportGroup)
    {
        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $id = $reportGroup instanceof ReportGroup ? $reportGroup->id : ($reportGroup instanceof LegacyReportGroup ? $reportGroup->id : $reportGroup);

        if (LegacyAccess::isAvailable('ref_report_group')) {
            $legacy = LegacyReportGroup::findOrFail($id);
            $legacy->update([
                'status' => $data['is_active'] ? '1' : '0',
                'updated_at' => now(),
            ]);

            return response()->json(['data' => $legacy->fresh()]);
        }

        $rg = $reportGroup instanceof ReportGroup ? $reportGroup : ReportGroup::findOrFail($id);
        $rg->update($data);

        return response()->json(['data' => $rg]);
    }

    public function destroy($reportGroup)
    {
        $id = $reportGroup instanceof ReportGroup ? $reportGroup->id : ($reportGroup instanceof LegacyReportGroup ? $reportGroup->id : $reportGroup);

        if (LegacyAccess::isAvailable('ref_report_group')) {
            $legacy = LegacyReportGroup::findOrFail($id);
            $legacy->delete();

            return response()->noContent();
        }

        $rg = $reportGroup instanceof ReportGroup ? $reportGroup : ReportGroup::findOrFail($id);
        $rg->delete();

        return response()->noContent();
    }
}

