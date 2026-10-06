<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;

class LookupController extends Controller
{
    public function employees(Request $request)
    {
        $query = Employee::with('doctor');
        
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->orderBy('name')->paginate((int) $request->get('per_page', 15)));
    }

    public function specializations(Request $request)
    {
        $query = \App\Models\MasterData\Specialization::query();
        
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->orderBy('name')->get());
    }

    // Legacy JSON lookup helper removed

    public function provinces()
    {
        return response()->json(\App\Models\Province::orderBy('name')->get());
    }

    public function regencies(Request $request)
    {
        $query = \App\Models\Regency::query();
        if ($request->has('province_id')) {
            $query->where('province_id', $request->province_id);
        }
        return response()->json($query->orderBy('name')->get());
    }

    public function districts(Request $request)
    {
        $query = \App\Models\District::query()
            ->with('regency:id,name')
            ->where('is_active', true)
            ->orderBy('name');

        if ($request->has('regency_id')) {
            $query->where('regency_id', $request->regency_id);
        }

        $districts = $query->get(['id', 'name', 'code', 'regency_id']);
        
        $mapped = $districts->map(function ($district) {
            return [
                'id' => $district->id,
                'name' => $district->name,
                'code' => $district->code,
                'regency_id' => $district->regency_id,
                'regency_name' => $district->regency ? $district->regency->name : null,
            ];
        });

        return response()->json($mapped);
    }

    public function villages(Request $request)
    {
        $query = \App\Models\Village::query();
        if ($request->has('district_id')) {
            $query->where('district_id', $request->district_id);
        }
        return response()->json($query->orderBy('name')->get());
    }

    public function educations(Request $request)
    {
        $query = \App\Models\Education::query();
        
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function occupations(Request $request)
    {
        $query = \App\Models\Occupation::where('is_active', true);

        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('name')->get());
    }
}
