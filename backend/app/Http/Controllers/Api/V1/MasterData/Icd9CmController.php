<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Icd9Cm;
use Illuminate\Http\Request;

class Icd9CmController extends Controller
{
    public function index(Request $request)
    {
        $query = Icd9Cm::query();

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active === 'true' || $request->is_active === '1');
        }

        $perPage = $request->input('per_page', 100);
        
        return \App\Http\Resources\Api\V1\MasterData\Icd9CmResource::collection($query->paginate($perPage));
    }

    public function show(Icd9Cm $icd9Cm)
    {
        return new \App\Http\Resources\Api\V1\MasterData\Icd9CmResource($icd9Cm);
    }
}
