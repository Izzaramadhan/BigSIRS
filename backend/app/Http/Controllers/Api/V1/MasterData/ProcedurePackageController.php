<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProcedurePackageRequest;
use App\Http\Requests\UpdateProcedurePackageRequest;
use App\Http\Resources\ProcedurePackageListResource;
use App\Http\Resources\ProcedurePackageResource;
use App\Models\Legacy\LegacyMedicalProcedure;
use App\Models\Legacy\LegacyProcedurePackage;
use App\Models\Legacy\LegacyProcedurePackageItem;
use App\Models\MasterData\MedicalProcedure;
use App\Models\MasterData\ProcedurePackage;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcedurePackageController extends Controller
{
    public function index(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_paket_tindakan')) {
            $query = LegacyProcedurePackage::with(['items.procedure.tariffType']);

            if ($request->filled('search')) {
                $query->search($request->input('search'));
            }

            if ($request->has('is_active') && $request->is_active !== '') {
                $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
                $query->where('status', $isActive ? '1' : '0');
            }

            $allowedSorts = ['name', 'nama', 'created_at'];
            $sortBy = in_array($request->sort_by, $allowedSorts) ? ($request->sort_by === 'name' ? 'nama' : $request->sort_by) : 'id';
            $sortDesc = filter_var($request->sort_desc ?? true, FILTER_VALIDATE_BOOLEAN);
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');

            $perPage = $request->per_page ?? 10;
            $packages = $query->paginate($perPage);

            return ProcedurePackageListResource::collection($packages);
        }

        $query = ProcedurePackage::with(['items.procedure']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhereHas('items.procedure', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $allowedSorts = ['name', 'total_amount', 'is_active', 'created_at'];
        $sortBy = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'created_at';
        $sortDesc = filter_var($request->sort_desc ?? true, FILTER_VALIDATE_BOOLEAN);
        $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');

        $perPage = $request->per_page ?? 10;
        $packages = $query->paginate($perPage);

        return ProcedurePackageListResource::collection($packages);
    }

    public function show($id)
    {
        if (LegacyAccess::isAvailable('ref_paket_tindakan')) {
            $package = LegacyProcedurePackage::with(['items.procedure.tariffType'])->findOrFail($id);

            return new ProcedurePackageResource($package);
        }

        $package = ProcedurePackage::with(['items.procedure', 'items.tariff.tariffType'])->findOrFail($id);

        return new ProcedurePackageResource($package);
    }

    public function store(StoreProcedurePackageRequest $request)
    {
        if (LegacyAccess::isAvailable('ref_paket_tindakan')) {
            $legacyUserId = $request->user()?->legacy_user_id ?? 1;

            return DB::connection('legacy')->transaction(function () use ($request, $legacyUserId) {
                $package = LegacyProcedurePackage::create([
                    'nama' => $request->name,
                    'status' => ($request->is_active ?? true) ? '1' : '0',
                    'id_user' => $legacyUserId,
                    'created_at' => now(),
                ]);

                foreach ($request->items as $itemData) {
                    $procedure = LegacyMedicalProcedure::findOrFail($itemData['medical_procedure_id']);
                    LegacyProcedurePackageItem::create([
                        'id_ref_paket_tindakan' => $package->id,
                        'id_ref_tarif_tindakan' => $procedure->id,
                        'status' => '1',
                        'id_user' => $legacyUserId,
                        'created_at' => now(),
                    ]);
                }

                $package->load(['items.procedure.tariffType']);

                return (new ProcedurePackageResource($package))->response()->setStatusCode(201);
            });
        }

        DB::beginTransaction();
        try {
            $package = ProcedurePackage::create([
                'name' => $request->name,
                'is_active' => $request->is_active ?? true,
                'total_amount' => 0, // will calculate later
            ]);

            $total = 0;
            $itemsData = $request->items;

            foreach ($itemsData as $index => $itemData) {
                // Verify procedure exists
                $procedure = MedicalProcedure::find($itemData['medical_procedure_id']);
                if (! $procedure) {
                    throw ValidationException::withMessages(["items.{$index}.medical_procedure_id" => ['Procedure not found']]);
                }

                $subtotal = $itemData['quantity'] * $itemData['unit_amount'];

                $package->items()->create([
                    'medical_procedure_id' => $itemData['medical_procedure_id'],
                    'medical_procedure_tariff_id' => $itemData['medical_procedure_tariff_id'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_amount' => $itemData['unit_amount'],
                    'subtotal_amount' => $subtotal,
                    'sort_order' => $itemData['sort_order'] ?? $index,
                ]);

                $total += $subtotal;
            }

            $package->update(['total_amount' => $total]);

            DB::commit();

            $package->load(['items.procedure', 'items.tariff.tariffType']);

            return (new ProcedurePackageResource($package))->response()->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(UpdateProcedurePackageRequest $request, $id)
    {
        if (LegacyAccess::isAvailable('ref_paket_tindakan')) {
            $package = LegacyProcedurePackage::findOrFail($id);
            $legacyUserId = $request->user()?->legacy_user_id ?? 1;

            return DB::connection('legacy')->transaction(function () use ($request, $package, $legacyUserId) {
                $package->update([
                    'nama' => $request->name,
                    'status' => isset($request->is_active) ? ($request->is_active ? '1' : '0') : $package->status,
                    'id_user' => $legacyUserId,
                    'updated_at' => now(),
                ]);

                $submittedItemIds = collect($request->items)->pluck('id')->filter()->toArray();

                // Delete removed items
                if (! empty($submittedItemIds)) {
                    $package->items()->whereNotIn('id', $submittedItemIds)->delete();
                } else {
                    $package->items()->delete();
                }

                foreach ($request->items as $itemData) {
                    $procedure = LegacyMedicalProcedure::findOrFail($itemData['medical_procedure_id']);
                    if (! empty($itemData['id'])) {
                        $item = $package->items()->find($itemData['id']);
                        if ($item) {
                            $item->update([
                                'id_ref_tarif_tindakan' => $procedure->id,
                                'id_user' => $legacyUserId,
                                'updated_at' => now(),
                            ]);
                        }
                    } else {
                        LegacyProcedurePackageItem::create([
                            'id_ref_paket_tindakan' => $package->id,
                            'id_ref_tarif_tindakan' => $procedure->id,
                            'status' => '1',
                            'id_user' => $legacyUserId,
                            'created_at' => now(),
                        ]);
                    }
                }

                $package->load(['items.procedure.tariffType']);

                return new ProcedurePackageResource($package);
            });
        }

        $package = ProcedurePackage::findOrFail($id);

        DB::beginTransaction();
        try {
            $package->update([
                'name' => $request->name,
                'is_active' => $request->is_active ?? $package->is_active,
            ]);

            $total = 0;
            $itemsData = $request->items;
            $submittedItemIds = collect($itemsData)->pluck('id')->filter()->toArray();

            // Delete removed items
            $package->items()->whereNotIn('id', $submittedItemIds)->delete();

            foreach ($itemsData as $index => $itemData) {
                $procedure = MedicalProcedure::find($itemData['medical_procedure_id']);
                if (! $procedure) {
                    throw ValidationException::withMessages(["items.{$index}.medical_procedure_id" => ['Procedure not found']]);
                }

                $subtotal = $itemData['quantity'] * $itemData['unit_amount'];

                if (! empty($itemData['id'])) {
                    $item = $package->items()->find($itemData['id']);
                    $item->update([
                        'medical_procedure_id' => $itemData['medical_procedure_id'],
                        'medical_procedure_tariff_id' => $itemData['medical_procedure_tariff_id'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'unit_amount' => $itemData['unit_amount'],
                        'subtotal_amount' => $subtotal,
                        'sort_order' => $itemData['sort_order'] ?? $index,
                    ]);
                } else {
                    $package->items()->create([
                        'medical_procedure_id' => $itemData['medical_procedure_id'],
                        'medical_procedure_tariff_id' => $itemData['medical_procedure_tariff_id'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'unit_amount' => $itemData['unit_amount'],
                        'subtotal_amount' => $subtotal,
                        'sort_order' => $itemData['sort_order'] ?? $index,
                    ]);
                }

                $total += $subtotal;
            }

            $package->update(['total_amount' => $total]);

            DB::commit();

            $package->load(['items.procedure', 'items.tariff.tariffType']);

            return new ProcedurePackageResource($package);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);

        if (LegacyAccess::isAvailable('ref_paket_tindakan')) {
            $package = LegacyProcedurePackage::findOrFail($id);
            $package->update([
                'status' => $request->is_active ? '1' : '0',
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'Status updated successfully']);
        }

        $package = ProcedurePackage::findOrFail($id);
        $package->update(['is_active' => $request->is_active]);

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function destroy($id)
    {
        if (LegacyAccess::isAvailable('ref_paket_tindakan')) {
            $package = LegacyProcedurePackage::findOrFail($id);
            DB::connection('legacy')->transaction(function () use ($package) {
                $package->items()->delete();
                $package->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Paket Tindakan berhasil dihapus.',
            ]);
        }

        $package = ProcedurePackage::findOrFail($id);
        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paket Tindakan berhasil dihapus.',
        ]);
    }
}

