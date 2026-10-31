<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouse_stocks', 'legacy_id')) {
                $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('warehouse_stocks', 'selling_price')) {
                $table->decimal('selling_price', 15, 2)->default(0)->after('quantity');
            }
            
            // Drop unique batch constraint so multiple lots/batches can be stored
            try {
                $table->dropUnique('uq_warehouse_medicine_batch');
            } catch (\Throwable) {
                // Ignore if constraint does not exist
            }
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'warehouse_stock_id')) {
                $table->foreignId('warehouse_stock_id')
                    ->nullable()
                    ->after('stock_opname_item_id')
                    ->constrained('warehouse_stocks')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'warehouse_stock_id')) {
                $table->dropForeign(['warehouse_stock_id']);
                $table->dropColumn('warehouse_stock_id');
            }
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_stocks', 'selling_price')) {
                $table->dropColumn('selling_price');
            }
            if (Schema::hasColumn('warehouse_stocks', 'legacy_id')) {
                $table->dropColumn('legacy_id');
            }
        });
    }
};

