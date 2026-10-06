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
        Schema::table('districts', function (Blueprint $table) {
            if (! Schema::hasColumn('districts', 'code')) {
                $table->string('code')->nullable()->after('legacy_id');
            }
            if (! Schema::hasColumn('districts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('regency_id');
            }
            if (! Schema::hasColumn('districts', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('districts', function (Blueprint $table) {
            $table->dropColumn(['code', 'is_active']);
            $table->dropSoftDeletes();
        });
    }
};
