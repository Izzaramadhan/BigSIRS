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
        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->decimal('total_value', 15, 2)->default(0)->after('notes');
        });

        Schema::table('stock_mutation_items', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->default(0)->after('quantity');
            $table->decimal('subtotal', 15, 2)->default(0)->after('unit_price');
        });

        // Backfill prices from medicines for existing mutation items
        $items = DB::table('stock_mutation_items')
            ->join('medicines', 'stock_mutation_items.medicine_id', '=', 'medicines.id')
            ->select(
                'stock_mutation_items.id',
                'stock_mutation_items.stock_mutation_id',
                'stock_mutation_items.quantity',
                'medicines.selling_price_per_unit'
            )
            ->get();

        foreach ($items as $item) {
            $unitPrice = (float) ($item->selling_price_per_unit ?? 0);
            $subtotal = (float) $item->quantity * $unitPrice;

            DB::table('stock_mutation_items')
                ->where('id', $item->id)
                ->update([
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
        }

        // Backfill total_value on stock_mutations
        $mutationTotals = DB::table('stock_mutation_items')
            ->select('stock_mutation_id', DB::raw('SUM(subtotal) as total'))
            ->groupBy('stock_mutation_id')
            ->get();

        foreach ($mutationTotals as $mt) {
            DB::table('stock_mutations')
                ->where('id', $mt->stock_mutation_id)
                ->update(['total_value' => (float) $mt->total]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_mutation_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'subtotal']);
        });

        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->dropColumn('total_value');
        });
    }
};

