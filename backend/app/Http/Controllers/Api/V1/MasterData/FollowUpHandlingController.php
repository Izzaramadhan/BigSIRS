<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreFollowUpHandlingRequest;
use App\Http\Requests\MasterData\UpdateFollowUpHandlingRequest;
use App\Http\Resources\MasterData\FollowUpHandlingResource;
use App\Models\Legacy\LegacyFollowUpHandling;
use App\Models\MasterData\FollowUpHandling;
use App\Support\LegacyAccess;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowUpHandlingController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', LegacyAccess::isAvailable('ref_penanganan_lanjutan') ? LegacyFollowUpHandling::class : FollowUpHandling::class);

        if (LegacyAccess::isAvailable('ref_penanganan_lanjutan')) {
            $query = LegacyFollowUpHandling::query()->orderBy('nama', 'asc');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('is_active')) {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            $perPage = $request->input('per_page', 10);
            if ($perPage === 'all') {
                return FollowUpHandlingResource::collection($query->get());
            }

            return FollowUpHandlingResource::collection($query->paginate((int) $perPage));
        }

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
        $this->authorize('create', LegacyAccess::isAvailable('ref_penanganan_lanjutan') ? LegacyFollowUpHandling::class : FollowUpHandling::class);

        if (LegacyAccess::isAvailable('ref_penanganan_lanjutan')) {
            $handling = DB::connection('legacy')->transaction(function () use ($request) {
                return LegacyFollowUpHandling::create($request->validated());
            });

            return (new FollowUpHandlingResource($handling))->response()->setStatusCode(201);
        }

        $handling = FollowUpHandling::create($request->validated());

        return new FollowUpHandlingResource($handling);
    }

    /**
     * Display the specified resource.
     */
    public function show($followUpHandling)
    {
        $id = $followUpHandling instanceof FollowUpHandling || $followUpHandling instanceof LegacyFollowUpHandling ? $followUpHandling->id : $followUpHandling;
        if (LegacyAccess::isAvailable('ref_penanganan_lanjutan')) {
            $legacy = LegacyFollowUpHandling::findOrFail($id);
            $this->authorize('view', $legacy);
            return new FollowUpHandlingResource($legacy);
        }

        if (! ($followUpHandling instanceof FollowUpHandling)) {
            $followUpHandling = FollowUpHandling::findOrFail($id);
        }

        $this->authorize('view', $followUpHandling);

        return new FollowUpHandlingResource($followUpHandling);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFollowUpHandlingRequest $request, $followUpHandling)
    {
        $id = $followUpHandling instanceof FollowUpHandling || $followUpHandling instanceof LegacyFollowUpHandling ? $followUpHandling->id : $followUpHandling;
        if (LegacyAccess::isAvailable('ref_penanganan_lanjutan')) {
            $legacy = LegacyFollowUpHandling::findOrFail($id);
            $this->authorize('update', $legacy);

            DB::connection('legacy')->transaction(function () use ($legacy, $request) {
                $legacy->update($request->validated());
            });

            return new FollowUpHandlingResource($legacy->fresh());
        }

        if (! ($followUpHandling instanceof FollowUpHandling)) {
            $followUpHandling = FollowUpHandling::findOrFail($id);
        }

        $this->authorize('update', $followUpHandling);

        $followUpHandling->update($request->validated());

        return new FollowUpHandlingResource($followUpHandling);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($followUpHandling)
    {
        $id = $followUpHandling instanceof FollowUpHandling || $followUpHandling instanceof LegacyFollowUpHandling ? $followUpHandling->id : $followUpHandling;
        if (LegacyAccess::isAvailable('ref_penanganan_lanjutan')) {
            $legacy = LegacyFollowUpHandling::findOrFail($id);
            $this->authorize('delete', $legacy);

            $isUsed = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('trx_visit')) {
                $isUsed = DB::connection('legacy')->table('trx_visit')
                    ->where('id_penanganan_lanjutan', $id)
                    ->exists();
            }

            if ($isUsed) {
                return response()->json([
                    'message' => 'Penanganan lanjutan tidak dapat dihapus karena sudah digunakan pada data pelayanan pasien.'
                ], 422);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($followUpHandling instanceof FollowUpHandling)) {
            $followUpHandling = FollowUpHandling::findOrFail($id);
        }

        $this->authorize('delete', $followUpHandling);

        $isUsed = false;
        if (\Illuminate\Support\Facades\Schema::hasTable('trx_visit')) {
            $isUsed = DB::table('trx_visit')
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
