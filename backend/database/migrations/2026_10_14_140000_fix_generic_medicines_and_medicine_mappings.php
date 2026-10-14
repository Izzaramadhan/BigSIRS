<?php

use App\Models\MasterData\GenericMedicine;
use App\Models\MasterData\Medicine;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. If legacy database is available, clean generic_medicines and remap medicines
        $hasLegacyConnection = false;
        try {
            DB::connection('legacy')->getPdo();
            $hasLegacyConnection = true;
        } catch (\Throwable $e) {
            $hasLegacyConnection = false;
        }

        if ($hasLegacyConnection && Schema::connection('legacy')->hasTable('ref_generik')) {
            // Find legacy IDs of non-medicine equipment (sediaan = '0')
            $equipmentLegacyIds = DB::connection('legacy')->table('ref_generik')
                ->where('sediaan', '0')
                ->pluck('id')
                ->toArray();

            // Nullify any medicines pointing to these equipment generics before deleting
            if (! empty($equipmentLegacyIds)) {
                $equipmentGenericIds = GenericMedicine::withTrashed()
                    ->whereIn('legacy_id', $equipmentLegacyIds)
                    ->pluck('id')
                    ->toArray();

                if (! empty($equipmentGenericIds)) {
                    Medicine::whereIn('generic_medicine_id', $equipmentGenericIds)
                        ->update(['generic_medicine_id' => null]);
                    GenericMedicine::whereIn('id', $equipmentGenericIds)->forceDelete();
                }
            }

            // Clean names in generic_medicines (trim whitespace and leading/trailing quotes/tabs)
            GenericMedicine::chunk(500, function ($generics) {
                foreach ($generics as $gen) {
                    $cleaned = trim($gen->name ?? '', " \t\n\r\0\x0B\"'");
                    if ($cleaned !== $gen->name) {
                        $gen->name = $cleaned;
                        $gen->save();
                    }
                }
            });

            // Re-map medicines.generic_medicine_id from ref_obat and ref_generik
            if (Schema::connection('legacy')->hasTable('ref_obat')) {
                // Map ref_generik where sediaan != '0'
                $validGenerics = DB::connection('legacy')->table('ref_generik')
                    ->where(function ($q) {
                        $q->whereNull('sediaan')->orWhere('sediaan', '!=', '0');
                    })
                    ->get(['id', 'id_hlp']);

                $mapGenericsByLegacyId = GenericMedicine::pluck('id', 'legacy_id')->toArray();
                $mapGenericsByHlp = [];
                foreach ($validGenerics as $lg) {
                    if (! empty($lg->id_hlp) && (int) $lg->id_hlp > 0 && isset($mapGenericsByLegacyId[$lg->id])) {
                        $mapGenericsByHlp[(int) $lg->id_hlp] = $mapGenericsByLegacyId[$lg->id];
                    }
                }

                $legacyMedicines = DB::connection('legacy')->table('ref_obat')->get(['id', 'id_generik']);
                foreach ($legacyMedicines as $med) {
                    $targetGenericId = null;
                    if (! empty($med->id_generik)) {
                        $idGen = (int) $med->id_generik;
                        if (isset($mapGenericsByHlp[$idGen])) {
                            $targetGenericId = $mapGenericsByHlp[$idGen];
                        } elseif (isset($mapGenericsByLegacyId[$idGen])) {
                            $targetGenericId = $mapGenericsByLegacyId[$idGen];
                        }
                    }

                    Medicine::where('legacy_id', $med->id)
                        ->update(['generic_medicine_id' => $targetGenericId]);
                }
            }
        } else {
            // Fallback if legacy connection is not present: remove known equipment patterns
            GenericMedicine::where('name', 'like', '"%')
                ->orWhere('name', 'like', "	%")
                ->orWhere('name', 'like', '%PERALATAN%')
                ->orWhere('name', 'like', '%test system%')
                ->forceDelete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed as this is a data cleaning migration
    }
};
