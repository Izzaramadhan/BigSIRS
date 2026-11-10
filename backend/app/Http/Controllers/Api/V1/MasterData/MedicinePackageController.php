<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreMedicinePackageRequest;
use App\Http\Requests\MasterData\UpdateMedicinePackageRequest;
use App\Http\Resources\MasterData\MedicinePackageResource;
use App\Models\Legacy\LegacyMedicinePackage;
use App\Models\Legacy\LegacyMedicinePackageItem;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MedicinePackageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', LegacyMedicinePackage::class);

        $perPage = $request->input('per_page', 10);
        $query = LegacyMedicinePackage::query()->with('items.medicine');

        if ($search = $request->get('search')) {
            $query->search($search);
        }

        if ($request->has('is_active')) {
            $isActive = $request->boolean('is_active');
            $query->where('status', $isActive ? '1' : '0');
        }

        $sortBy = $request->input('sort', $request->input('sortBy', 'name'));
        if ($request->has('direction')) {
            $sortDesc = strtolower($request->input('direction')) === 'desc';
        } else {
            $sortDesc = filter_var($request->input('sortDesc', false), FILTER_VALIDATE_BOOLEAN);
        }

        $columnMap = [
            'id' => 'id',
            'name' => 'nama',
            'price' => 'harga',
            'quantity' => 'jumlah',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $sortCol = $columnMap[$sortBy] ?? 'nama';
        $query->orderBy($sortCol, $sortDesc ? 'desc' : 'asc');

        if ($perPage === '-1' || $perPage === 'all') {
            return MedicinePackageResource::collection($query->get());
        }

        return MedicinePackageResource::collection($query->paginate((int) $perPage));
    }

    public function store(StoreMedicinePackageRequest $request)
    {
        Gate::authorize('create', LegacyMedicinePackage::class);

        $data = $request->validated();
        $user = $request->user();
        $legacyUserId = $user?->getLegacyUserId();

        if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
            return response()->json([
                'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
            ], 403);
        }

        $medicinePackage = DB::connection('legacy')->transaction(function () use ($data, $legacyUserId) {
            $items = $data['items'];
            $calculatedTotal = 0;
            foreach ($items as $item) {
                $calculatedTotal += round(((int) $item['quantity']) * ((float) $item['unit_price']), 2);
            }

            $package = new LegacyMedicinePackage();
            $package->name = $data['name'];
            $package->price = (! empty($data['price']) && $data['price'] > 0) ? $data['price'] : $calculatedTotal;
            if (array_key_exists('quantity', $data)) {
                $package->quantity = $data['quantity'];
            }
            if (array_key_exists('description', $data)) {
                $package->description = $data['description'];
            }
            if (isset($data['is_active'])) {
                $package->is_active = $data['is_active'];
            } else {
                $package->is_active = true;
            }
            if ($legacyUserId) {
                $package->id_user = (string) $legacyUserId;
            }
            $package->save();

            foreach ($items as $item) {
                $subtotal = round(((int) $item['quantity']) * ((float) $item['unit_price']), 2);
                $packageItem = new LegacyMedicinePackageItem();
                $packageItem->medicine_package_id = $package->id;
                $packageItem->medicine_id = $item['medicine_id'];
                $packageItem->quantity = (int) $item['quantity'];
                $packageItem->subtotal = $subtotal;
                $packageItem->status = '1';
                if ($legacyUserId) {
                    $packageItem->id_user = (string) $legacyUserId;
                }
                $packageItem->save();
            }

            return $package->load('items.medicine');
        });

        return (new MedicinePackageResource($medicinePackage))->response()->setStatusCode(201);
    }

    public function show($medicinePackage)
    {
        $id = $medicinePackage instanceof \Illuminate\Database\Eloquent\Model ? $medicinePackage->id : $medicinePackage;

        $legacy = LegacyMedicinePackage::with('items.medicine')->findOrFail($id);
        Gate::authorize('view', $legacy);

        return new MedicinePackageResource($legacy);
    }

    public function update(UpdateMedicinePackageRequest $request, $medicinePackage)
    {
        $id = $medicinePackage instanceof \Illuminate\Database\Eloquent\Model ? $medicinePackage->id : $medicinePackage;

        $legacy = LegacyMedicinePackage::findOrFail($id);
        Gate::authorize('update', $legacy);

        $user = $request->user();
        $legacyUserId = $user?->getLegacyUserId();

        if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
            return response()->json([
                'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
            ], 403);
        }

        $data = $request->validated();

        $updated = DB::connection('legacy')->transaction(function () use ($legacy, $data, $legacyUserId) {
            $items = $data['items'];
            $calculatedTotal = 0;
            foreach ($items as $item) {
                $calculatedTotal += round(((int) $item['quantity']) * ((float) $item['unit_price']), 2);
            }

            $legacy->name = $data['name'];
            $legacy->price = (! empty($data['price']) && $data['price'] > 0) ? $data['price'] : $calculatedTotal;
            if (array_key_exists('quantity', $data)) {
                $legacy->quantity = $data['quantity'];
            }
            if (array_key_exists('description', $data)) {
                $legacy->description = $data['description'];
            }
            if (isset($data['is_active'])) {
                $legacy->is_active = $data['is_active'];
            }
            if ($legacyUserId) {
                $legacy->id_user = (string) $legacyUserId;
            }
            $legacy->save();

            // Delete old items and recreate
            $legacy->items()->delete();

            foreach ($items as $item) {
                $subtotal = round(((int) $item['quantity']) * ((float) $item['unit_price']), 2);
                $packageItem = new LegacyMedicinePackageItem();
                $packageItem->medicine_package_id = $legacy->id;
                $packageItem->medicine_id = $item['medicine_id'];
                $packageItem->quantity = (int) $item['quantity'];
                $packageItem->subtotal = $subtotal;
                $packageItem->status = '1';
                if ($legacyUserId) {
                    $packageItem->id_user = (string) $legacyUserId;
                }
                $packageItem->save();
            }

            return $legacy->load('items.medicine');
        });

        return new MedicinePackageResource($updated);
    }

    public function destroy($medicinePackage)
    {
        $id = $medicinePackage instanceof \Illuminate\Database\Eloquent\Model ? $medicinePackage->id : $medicinePackage;

        $legacy = LegacyMedicinePackage::findOrFail($id);
        Gate::authorize('delete', $legacy);

        $user = request()->user();
        $legacyUserId = $user?->getLegacyUserId();

        if (LegacyAccess::isAvailable('tbl_user') && ! $legacyUserId) {
            return response()->json([
                'message' => 'Identitas pengguna tidak terdaftar atau tidak dapat dipetakan ke database SIMRS.',
            ], 403);
        }

        if ($legacy->isReferencedInTransactions()) {
            return response()->json([
                'message' => 'Paket obat tidak dapat dihapus karena masih digunakan pada data transaksi.',
            ], 409);
        }

        DB::connection('legacy')->transaction(function () use ($legacy, $legacyUserId) {
            $legacy->performSoftDelete($legacyUserId);
        });

        return response()->noContent();
    }
}
