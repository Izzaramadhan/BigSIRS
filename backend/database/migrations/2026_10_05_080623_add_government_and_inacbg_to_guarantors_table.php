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
        Schema::table('guarantors', function (Blueprint $table) {
            $table->boolean('is_government')->default(false)->after('name');
            $table->string('inacbg_id', 10)->nullable()->after('is_government');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guarantors', function (Blueprint $table) {
            $table->dropColumn(['is_government', 'inacbg_id']);
        });
    }
};
