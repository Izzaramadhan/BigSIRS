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
        Schema::rename('cities', 'regencies');

        Schema::table('regencies', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('legacy_id');
            $table->boolean('is_active')->default(true)->after('province_id');
            $table->softDeletes();
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->renameColumn('city_id', 'regency_id');
            $table->foreign('regency_id')->references('id')->on('regencies')->onDelete('cascade');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('city_id', 'regency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('regency_id', 'city_id');
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->dropForeign(['regency_id']);
            $table->renameColumn('regency_id', 'city_id');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });

        Schema::table('regencies', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['code', 'is_active']);
        });

        Schema::rename('regencies', 'cities');
    }
};
