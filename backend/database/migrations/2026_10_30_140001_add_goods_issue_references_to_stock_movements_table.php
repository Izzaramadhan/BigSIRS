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
            $table->foreignId('goods_issue_id')->nullable()->after('stock_opname_item_id')->constrained('goods_issues')->nullOnDelete();
            $table->foreignId('goods_issue_item_id')->nullable()->after('goods_issue_id')->constrained('goods_issue_items')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['goods_issue_item_id']);
            $table->dropForeign(['goods_issue_id']);
            $table->dropColumn(['goods_issue_id', 'goods_issue_item_id']);
        });
    }
};

