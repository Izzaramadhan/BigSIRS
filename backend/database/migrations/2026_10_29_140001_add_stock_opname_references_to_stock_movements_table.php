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
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('stock_opname_id')->nullable()->after('stock_mutation_item_id')->constrained('stock_opnames')->nullOnDelete();
            $table->foreignId('stock_opname_item_id')->nullable()->after('stock_opname_id')->constrained('stock_opname_items')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['stock_opname_item_id']);
            $table->dropForeign(['stock_opname_id']);
            $table->dropColumn(['stock_opname_id', 'stock_opname_item_id']);
        });
    }
};

