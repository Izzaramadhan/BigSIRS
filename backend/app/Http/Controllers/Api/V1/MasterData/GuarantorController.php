<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuarantorRequest;
use App\Http\Requests\UpdateGuarantorRequest;
use App\Http\Requests\UpdateGuarantorStatusRequest;
use App\Http\Resources\GuarantorResource;
use App\Models\Guarantor;
use App\Models\Legacy\LegacyGuarantor;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuarantorController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $sort = $request->query('sort', 'name');
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'code', 'name', 'type', 'created_at', 'updated_at', 'is_active', 'is_government', 'inacbg_id'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        if (LegacyAccess::isAvailable('ref_jenis_asuransi')) {
            $query = LegacyGuarantor::query();

            if ($request->has('search') && $request->search !== null) {
                $query->search($request->search);
            }

            if ($request->has('type') && $request->type !== null) {
                $query->where('type', $request->type);
            }

            if ($request->has('is_active') && $request->is_active !== null) {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? 1 : 0);
            }

            $dbSort = match ($sort) {
                'name' => 'jenis_asuransi',
                'code' => 'kode',
                'is_active' => 'status',
                'is_government' => 'pemerintah',
                'inacbg_id' => 'id_inacbg',
                default => 'jenis_asuransi',
            };

            return GuarantorResource::collection($query->orderBy($dbSort, $direction)->paginate($perPage));
        }

        $query = Guarantor::query();

        if ($request->has('search') && $request->search !== null) {
            $query->search($request->search);
        }

        if ($request->has('type') && $request->type !== null) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $guarantors = $query->orderBy($sort, $direction)->paginate($perPage);

        return GuarantorResource::collection($guarantors);
    }

    public function store(StoreGuarantorRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_jenis_asuransi')) {
            return DB::connection('legacy')->transaction(function () use ($request) {
                $guarantor = LegacyGuarantor::create($request->validated());

                return (new GuarantorResource($guarantor))->response()->setStatusCode(201);
            });
        }

        return DB::transaction(function () use ($request) {
            $guarantor = Guarantor::create($request->validated());

            return new GuarantorResource($guarantor);
        });
    }

    public function show($guarantor)
    {
        $id = $guarantor instanceof Guarantor || $guarantor instanceof LegacyGuarantor ? $guarantor->id : $guarantor;
        if (LegacyAccess::isAvailable('ref_jenis_asuransi')) {
            $legacy = LegacyGuarantor::findOrFail($id);
            return new GuarantorResource($legacy);
        }

        if (! ($guarantor instanceof Guarantor)) {
            $guarantor = Guarantor::findOrFail($id);
        }

        return new GuarantorResource($guarantor);
    }

    public function update(UpdateGuarantorRequest $request, $guarantor)
    {
        $id = $guarantor instanceof Guarantor || $guarantor instanceof LegacyGuarantor ? $guarantor->id : $guarantor;
        if (LegacyAccess::isAvailable('ref_jenis_asuransi')) {
            $legacy = LegacyGuarantor::findOrFail($id);
            return DB::connection('legacy')->transaction(function () use ($request, $legacy) {
                $legacy->update($request->validated());

                return new GuarantorResource($legacy->fresh());
            });
        }

        if (! ($guarantor instanceof Guarantor)) {
            $guarantor = Guarantor::findOrFail($id);
        }

        return DB::transaction(function () use ($request, $guarantor) {
            $guarantor->update($request->validated());

            return new GuarantorResource($guarantor);
        });
    }

    public function status(UpdateGuarantorStatusRequest $request, $guarantor)
    {
        $id = $guarantor instanceof Guarantor || $guarantor instanceof LegacyGuarantor ? $guarantor->id : $guarantor;
        if (LegacyAccess::isAvailable('ref_jenis_asuransi')) {
            $legacy = LegacyGuarantor::findOrFail($id);
            return DB::connection('legacy')->transaction(function () use ($request, $legacy) {
                $legacy->update($request->validated());

                return new GuarantorResource($legacy->fresh());
            });
        }

        if (! ($guarantor instanceof Guarantor)) {
            $guarantor = Guarantor::findOrFail($id);
        }

        return DB::transaction(function () use ($request, $guarantor) {
            $guarantor->update($request->validated());

            return new GuarantorResource($guarantor);
        });
    }

    public function destroy($guarantor)
    {
        $id = $guarantor instanceof Guarantor || $guarantor instanceof LegacyGuarantor ? $guarantor->id : $guarantor;
        if (LegacyAccess::isAvailable('ref_jenis_asuransi')) {
            $legacy = LegacyGuarantor::findOrFail($id);

            $isUsed = false;
            if (DB::connection('legacy')->getSchemaBuilder()->hasTable('trx_admisi')) {
                $isUsed = DB::connection('legacy')->table('trx_admisi')
                    ->where('id_jenis_asuransi', $id)
                    ->exists();
            }

            if ($isUsed) {
                return response()->json([
                    'message' => 'Penjamin tidak dapat dihapus karena sudah digunakan pada data admisi pasien.'
                ], 422);
            }

            DB::connection('legacy')->transaction(function () use ($legacy) {
                $legacy->delete();
            });

            return response()->noContent();
        }

        if (! ($guarantor instanceof Guarantor)) {
            $guarantor = Guarantor::findOrFail($id);
        }

        DB::transaction(function () use ($guarantor) {
            $guarantor->delete();
        });

        return response()->noContent();
    }
}
