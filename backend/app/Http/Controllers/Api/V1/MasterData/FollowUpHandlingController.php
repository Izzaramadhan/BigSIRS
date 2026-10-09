<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreFollowUpHandlingRequest;
use App\Http\Requests\MasterData\UpdateFollowUpHandlingRequest;
use App\Http\Resources\MasterData\FollowUpHandlingResource;
use App\Models\MasterData\FollowUpHandling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FollowUpHandlingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', FollowUpHandling::class);

        $query = FollowUpHandling::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 10);
        
        if ($perPage === 'all') {
            return FollowUpHandlingResource::collection($query->get());
        }

        return FollowUpHandlingResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFollowUpHandlingRequest $request)
    {
        Gate::authorize('create', FollowUpHandling::class);

        $handling = FollowUpHandling::create($request->validated());

        return new FollowUpHandlingResource($handling);
    }

    /**
     * Display the specified resource.
     */
    public function show(FollowUpHandling $followUpHandling)
    {
        Gate::authorize('view', $followUpHandling);

        return new FollowUpHandlingResource($followUpHandling);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFollowUpHandlingRequest $request, FollowUpHandling $followUpHandling)
    {
        Gate::authorize('update', $followUpHandling);

        $followUpHandling->update($request->validated());

        return new FollowUpHandlingResource($followUpHandling);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FollowUpHandling $followUpHandling)
    {
        Gate::authorize('delete', $followUpHandling);

        // Protection: Check if used in transactions (e.g. trx_visit)
        $isUsed = false;
        if (\Illuminate\Support\Facades\Schema::hasTable('trx_visit')) {
            $isUsed = \Illuminate\Support\Facades\DB::table('trx_visit')
                ->where('id_penanganan_lanjutan', $followUpHandling->id)
                ->exists();
        }
            
        if ($isUsed) {
            return response()->json([
                'message' => 'Penanganan lanjutan tidak dapat dihapus karena sudah digunakan pada data pelayanan pasien.'
            ], 422);
        }

        $followUpHandling->delete();

        return response()->noContent();
    }
}
