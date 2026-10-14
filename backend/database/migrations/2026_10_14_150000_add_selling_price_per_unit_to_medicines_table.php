<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('medicines', 'selling_price_per_unit')) {
            Schema::table('medicines', function (Blueprint $table) {
                $table->decimal('selling_price_per_unit', 15, 2)->nullable()->after('generic_medicine_id');
            });
        }

        // Populate selling_price_per_unit from legacy log stok if legacy connection is available
        try {
            $legacy = DB::connection('legacy');
            $latestPrices = $legacy->table('lgs_faktur_detail as fd')
                ->join('lgs_log_stok as ls', 'ls.id_lgs_faktur_detail', '=', 'fd.id')
                ->where('ls.harga_jual', '>', 0)
                ->whereNull('ls.deleted_at')
                ->whereNull('fd.deleted_at')
                ->select('fd.id_obat', 'ls.harga_jual')
                ->orderBy('fd.id_obat')
                ->orderByDesc('ls.id')
                ->get()
                ->unique('id_obat')
                ->keyBy('id_obat');

            foreach ($latestPrices as $idObat => $log) {
                DB::table('medicines')
                    ->where('legacy_id', $idObat)
                    ->update([
                        'selling_price_per_unit' => $log->harga_jual,
                    ]);
            }
        } catch (\Throwable $e) {
            // Silently continue if legacy db is not reachable (e.g. in standalone testing)
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('medicines', 'selling_price_per_unit')) {
            Schema::table('medicines', function (Blueprint $table) {
                $table->dropColumn('selling_price_per_unit');
            });
        }
    }
};
