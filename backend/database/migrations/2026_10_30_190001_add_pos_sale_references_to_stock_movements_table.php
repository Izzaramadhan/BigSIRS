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
            $table->foreignId('pos_sale_id')->nullable()->after('goods_issue_item_id')->constrained('pos_sales')->nullOnDelete();
            $table->foreignId('pos_sale_item_id')->nullable()->after('pos_sale_id')->constrained('pos_sale_items')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['pos_sale_item_id']);
            $table->dropForeign(['pos_sale_id']);
            $table->dropColumn(['pos_sale_id', 'pos_sale_item_id']);
        });
    }
};

