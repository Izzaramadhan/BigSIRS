<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GuarantorResource;
use App\Models\District;
use App\Models\Education;
use App\Models\Employee;
use App\Models\Guarantor;
use App\Models\Legacy\LegacyDistrict;
use App\Models\Legacy\LegacyEducation;
use App\Models\Legacy\LegacyEmployee;
use App\Models\Legacy\LegacyGenericMedicine;
use App\Models\Legacy\LegacyGuarantor;
use App\Models\Legacy\LegacyLaboratoryCategory;
use App\Models\Legacy\LegacyMedicationSigna;
use App\Models\Legacy\LegacyMedicine;
use App\Models\Legacy\LegacyMedicineCategory;
use App\Models\Legacy\LegacyMedicineClassification;
use App\Models\Legacy\LegacyMedicineRoute;
use App\Models\Legacy\LegacyMedicineUnit;
use App\Models\Legacy\LegacyOccupation;
use App\Models\Legacy\LegacyPosition;
use App\Models\Legacy\LegacyProvince;
use App\Models\Legacy\LegacyRadiologyCategory;
use App\Models\Legacy\LegacyRadiologyItemGroup;
use App\Models\Legacy\LegacyRadiologyType;
use App\Models\Legacy\LegacyReferralSource;
use App\Models\Legacy\LegacyRegency;
use App\Models\Legacy\LegacySpecialization;
use App\Models\Legacy\LegacySupplier;
use App\Models\Legacy\LegacyVillage;
use App\Models\Legacy\LegacyWarehouse;
use App\Models\MasterData\LaboratoryCategory;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\MedicineClassification;
use App\Models\MasterData\MedicineUnit;
use App\Models\MasterData\Position;
use App\Models\MasterData\RadiologyCategory;
use App\Models\MasterData\RadiologyItemGroup;
use App\Models\MasterData\RadiologyType;
use App\Models\MasterData\Specialization;
use App\Models\MasterData\Supplier;
use App\Models\MasterData\Warehouse;
use App\Models\Occupation;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use App\Support\LegacyAccess;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function employees(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_pegawai')) {
            $query = LegacyEmployee::with(['person', 'position']);

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('is_active')) {
                $query->where('status', $request->boolean('is_active') ? '1' : '0');
            }

            return response()->json($query->paginate((int) $request->get('per_page', 15)));
        }

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
        if (LegacyAccess::isAvailable('ref_spesialisasi')) {
            $query = LegacySpecialization::query();

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('is_active')) {
                $query->where('status', $request->boolean('is_active') ? '1' : '0');
            }

            return response()->json($query->orderBy('nama_spesialisasi')->get());
        }

        $query = Specialization::query();

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

    public function provinces()
    {
        if (LegacyAccess::isAvailable('ref_provinsi')) {
            return response()->json(LegacyProvince::orderBy('nama')->get());
        }

        return response()->json(Province::orderBy('name')->get());
    }

    public function regencies(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_kabupaten')) {
            $query = LegacyRegency::query();
            if ($request->has('province_id')) {
                $query->where('id_provinsi', $request->province_id);
            }

            return response()->json($query->orderBy('nama')->get());
        }

        $query = Regency::query();
        if ($request->has('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function districts(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_kecamatan')) {
            $query = LegacyDistrict::query()
                ->with('regency')
                ->where('status', 1)
                ->orderBy('nama');

            if ($request->has('regency_id')) {
                $query->where('id_kabupaten', $request->regency_id);
            }

            $districts = $query->get();

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

        $query = District::query()
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
        if (LegacyAccess::isAvailable('ref_kelurahan')) {
            $query = LegacyVillage::query()
                ->with('district.regency.province')
                ->where('status', 1)
                ->orderBy('nama');

            if ($request->has('district_id')) {
                $query->where('id_kecamatan', $request->district_id);
            }

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('id')) {
                $query->where('id', $request->id);
            }

            $villages = $query->limit(50)->get();

            $mapped = $villages->map(function ($village) {
                $district = $village->district;
                $regency = $district ? $district->regency : null;
                $province = $regency ? $regency->province : null;

                return [
                    'id' => (string) $village->id,
                    'code' => $village->code ? (string) $village->code : null,
                    'name' => $village->name,
                    'district_id' => $district ? (string) $district->id : ($village->district_id ? (string) $village->district_id : null),
                    'district_name' => $district ? $district->name : null,
                    'regency_id' => $regency ? (string) $regency->id : null,
                    'regency_name' => $regency ? $regency->name : null,
                    'province_id' => $province ? (string) $province->id : ($regency && isset($regency->province_id) ? (string) $regency->province_id : null),
                    'province_name' => $province ? $province->name : null,
                    'postal_code' => !empty($village->kode_pos) ? (string) $village->kode_pos : null,
                ];
            });

            return response()->json($mapped);
        }

        $query = Village::query()
            ->with('district.regency.province')
            ->where('is_active', true)
            ->orderBy('name');

        if ($request->has('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('id')) {
            $query->where('id', $request->id);
        }

        // Limit to 50 results to prevent massive payloads during search
        $villages = $query->limit(50)->get();

        $mapped = $villages->map(function ($village) {
            $district = $village->district;
            $regency = $district ? $district->regency : null;
            $province = $regency ? $regency->province : null;

            return [
                'id' => (string) $village->id,
                'code' => $village->code ? (string) $village->code : null,
                'name' => $village->name,
                'district_id' => $district ? (string) $district->id : ($village->district_id ? (string) $village->district_id : null),
                'district_name' => $district ? $district->name : null,
                'regency_id' => $regency ? (string) $regency->id : null,
                'regency_name' => $regency ? $regency->name : null,
                'province_id' => $province ? (string) $province->id : ($regency && isset($regency->province_id) ? (string) $regency->province_id : null),
                'province_name' => $province ? $province->name : null,
                'postal_code' => !empty($village->postal_code) ? (string) $village->postal_code : null,
            ];
        });

        return response()->json($mapped);
    }

    public function educations(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_pendidikan')) {
            $query = LegacyEducation::query();

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('is_active')) {
                $query->where('status', $request->boolean('is_active') ? '1' : '0');
            }

            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            return response()->json($query->orderBy('pendidikan')->get());
        }

        $query = Education::query();

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
        if (LegacyAccess::isAvailable('ref_pekerjaan')) {
            $query = LegacyOccupation::where('status', '1');

            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            return response()->json($query->orderBy('pekerjaan')->get());
        }

        $query = Occupation::where('is_active', true);

        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function positions(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_jabatan')) {
            $query = LegacyPosition::query();

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            return response()->json($query->orderBy('jabatan')->get());
        }

        $query = Position::query();

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function referralSources(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_asal_rujukan')) {
            $query = LegacyReferralSource::active();

            if ($request->filled('search')) {
                $term = $request->search;
                $query->where(function ($q) use ($term) {
                    $q->where('nama', 'like', "%{$term}%")
                        ->orWhere('kode', 'like', "%{$term}%");
                });
            }

            $sources = $query->orderBy('id')->get()->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'code' => $item->kode,
                    'name' => $item->nama,
                    'is_inacbg' => (bool) $item->is_inacbg,
                ];
            });

            return response()->json($sources);
        }

        return response()->json([]);
    }

    public function laboratoryCategories(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_kategori_lab')) {
            $query = LegacyLaboratoryCategory::where('status', '1');

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            return response()->json($query->orderBy('nama')->get());
        }

        $query = LaboratoryCategory::where('is_active', true);

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function radiologyCategories(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_kategori_rad')) {
            $query = LegacyRadiologyCategory::where('status', '1');

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            return response()->json($query->orderBy('nama')->get());
        }

        $query = RadiologyCategory::where('is_active', true);

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function radiologyTypes(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_tipe_rad')) {
            $query = LegacyRadiologyType::where('status', 1);

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            return response()->json($query->orderBy('nama')->get());
        }

        $query = RadiologyType::where('is_active', true);

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function radiologyItemGroups(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_kelompok_item_rad')) {
            $query = LegacyRadiologyItemGroup::where('status', '1');

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            return response()->json($query->orderBy('nama')->get());
        }

        $query = RadiologyItemGroup::where('is_active', true);

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function medicineUnits(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_satuan_obat')) {
            $query = LegacyMedicineUnit::where('status', '1');
            if ($search = $request->get('search')) {
                $query->search($search);
            }
            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            return response()->json($query->orderBy('nama')->get());
        }

        $query = MedicineUnit::where('is_active', true);
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function warehouses(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_gudang')) {
            $query = LegacyWarehouse::query();
            if ($search = $request->get('search')) {
                $query->search($search);
            }
            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            return response()->json($query->orderBy('nama')->get());
        }

        $query = Warehouse::query();
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function suppliers(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_supplier')) {
            $query = LegacySupplier::where('status', 1);
            if ($search = $request->get('search')) {
                $query->search($search);
            }
            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            if (! $request->has('all')) {
                $limit = (int) $request->get('limit', $request->get('per_page', 100));
                $query->limit($limit);
            }

            return response()->json($query->orderBy('nama')->get());
        }

        $query = Supplier::where('is_active', true);
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        if (! $request->has('all')) {
            $limit = (int) $request->get('limit', $request->get('per_page', 100));
            $query->limit($limit);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function medicineCategories(Request $request)
    {
        $query = LegacyMedicineCategory::where('status', '1');
        if ($search = $request->get('search')) {
            $query->search($search);
        }
        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('nama')->get());
    }

    public function medicineClassifications(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_obat_golongan')) {
            $query = LegacyMedicineClassification::where('status', 1);
            if ($search = $request->get('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%");
                });
            }
            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            return response()->json($query->orderBy('name')->get());
        }

        $query = MedicineClassification::where('is_active', true);
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function medicineRoutes(Request $request)
    {
        $query = LegacyMedicineRoute::where('status', 1);
        if ($search = $request->get('search')) {
            $query->search($search);
        }
        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('rute_obat')->get());
    }

    public function medicationSignas(Request $request)
    {
        $query = LegacyMedicationSigna::where('status', 1);
        if ($search = $request->get('search')) {
            $query->search($search);
        }
        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        return response()->json($query->orderBy('nama')->get());
    }

    public function genericMedicines(Request $request)
    {
        $query = LegacyGenericMedicine::query();

        if ($request->has('is_active')) {
            $query->where('status', $request->boolean('is_active') ? 1 : 0);
        } else {
            $query->where('status', 1);
        }

        if ($search = $request->get('search')) {
            $query->where('nama_objek', 'like', "%{$search}%");
        }

        $limit = (int) $request->get('limit', $request->get('per_page', 50));

        if ($request->filled('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $selected = LegacyGenericMedicine::whereIn('id', $ids)->get();
            $items = $query->orderBy('nama_objek')->limit($limit)->get();

            return response()->json($selected->merge($items)->unique('id')->values());
        }

        return response()->json($query->orderBy('nama_objek')->limit($limit)->get());
    }

    public function medicines(Request $request)
    {
        if (LegacyAccess::isAvailable('ref_obat')) {
            $query = LegacyMedicine::with('unit')->where('status', '1');

            if ($search = $request->get('search')) {
                $query->search($search);
            }

            if ($request->has('ids')) {
                $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
                $query->orWhereIn('id', $ids);
            }

            if (! $request->has('all')) {
                $limit = (int) $request->get('limit', $request->get('per_page', 100));
                $query->limit($limit);
            }

            $items = $query->orderBy('nama')->get();

            return response()->json($items->map(function ($med) {
                return [
                    'id' => $med->id,
                    'name' => $med->name,
                    'code' => $med->code,
                    'kfa_code' => $med->kfa_code,
                    'generic_name' => null,
                    'unit' => $med->unit?->name ?? 'Box',
                    'price' => (float) ($med->selling_price_per_unit ?? 0),
                ];
            }));
        }

        $query = Medicine::with(['unit', 'generic'])->where('is_active', true);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('kfa_code', 'like', "%{$search}%")
                    ->orWhereHas('generic', function ($gq) use ($search) {
                        $gq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->orWhereIn('id', $ids);
        }

        if (! $request->has('all')) {
            $limit = (int) $request->get('limit', $request->get('per_page', 100));
            $query->limit($limit);
        }

        $items = $query->orderBy('name')->get();

            return response()->json($items->map(function ($med) {
            return [
                'id' => $med->id,
                'name' => $med->name,
                'code' => $med->code,
                'kfa_code' => $med->kfa_code,
                'generic_name' => $med->generic?->name,
                'unit' => $med->unit?->name ?? 'Box',
                'price' => (float) ($med->selling_price_per_unit ?? 0),
            ];
        }));
    }

    public function guarantors(Request $request)
    {
        $limit = max(1, min(500, (int) $request->get('limit', $request->get('per_page', 200))));
        $search = trim((string) $request->get('search', ''));
        $ids = [];
        if ($request->filled('ids')) {
            $rawIds = is_array($request->ids) ? $request->ids : explode(',', (string) $request->ids);
            $ids = array_values(array_filter(array_map('intval', $rawIds), fn ($id) => $id > 0));
        }

        if (LegacyAccess::isAvailable('ref_jenis_asuransi')) {
            $query = LegacyGuarantor::withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class)
                ->where(function ($q) {
                    $q->whereNull('deleted_at')
                        ->orWhere('deleted_at', '0000-00-00 00:00:00');
                });

            if ($request->has('is_active') && $request->get('is_active') !== 'all') {
                $query->where('status', $request->boolean('is_active') ? 1 : 0);
            } elseif (! $request->boolean('include_inactive') && $request->get('is_active') !== 'all') {
                $query->where('status', 1);
            }

            if ($search !== '') {
                $query->search($search);
            }

            $items = $query->orderBy('jenis_asuransi')->limit($limit)->get();

            if (! empty($ids)) {
                $selected = LegacyGuarantor::withTrashed()->whereIn('id', $ids)->get();
                $items = $selected->merge($items)->unique('id')->values();
            }

            return response()->json(GuarantorResource::collection($items)->resolve($request));
        }

        $query = Guarantor::query();

        if ($request->has('is_active') && $request->get('is_active') !== 'all') {
            $query->where('is_active', $request->boolean('is_active'));
        } elseif (! $request->boolean('include_inactive') && $request->get('is_active') !== 'all') {
            $query->where('is_active', true);
        }

        if ($search !== '') {
            $query->search($search);
        }

        $items = $query->orderBy('name')->limit($limit)->get();

        if (! empty($ids)) {
            $selected = Guarantor::withTrashed()->whereIn('id', $ids)->get();
            $items = $selected->merge($items)->unique('id')->values();
        }

        return response()->json(GuarantorResource::collection($items)->resolve($request));
    }
}
