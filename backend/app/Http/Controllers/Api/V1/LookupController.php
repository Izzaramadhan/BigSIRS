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

    public function cities(Request $request)
    {
        $query = \App\Models\City::query();
        if ($request->has('province_id')) {
            $query->where('province_id', $request->province_id);
        }
        return response()->json($query->orderBy('name')->get());
    }

    public function districts(Request $request)
    {
        $query = \App\Models\District::query();
        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }
        return response()->json($query->orderBy('name')->get());
    }

    public function villages(Request $request)
    {
        $query = \App\Models\Village::query();
        if ($request->has('district_id')) {
            $query->where('district_id', $request->district_id);
        }
        return response()->json($query->orderBy('name')->get());
    }

    public function educations()
    {
        return response()->json(\App\Models\Education::orderBy('name')->get());
    }

    public function occupations()
    {
        return response()->json(\App\Models\Occupation::orderBy('name')->get());
    }
}
