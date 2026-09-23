<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Additive migration — adds operational fields from legacy ref_poliklinik.
     * Does NOT drop parent_id or satusehat_code.
     * All new columns use safe defaults so existing 136 records remain intact.
     */
    public function up(): void
    {
        Schema::table('polyclinics', function (Blueprint $table) {
            // Service type (jenis) — enum matching legacy values
            $table->string('service_type', 30)->nullable()->after('name');

            // Description (deskripsi)
            $table->string('description', 255)->nullable()->after('service_type');

            // Visibility (tampil) — defaults to true for all existing records
            $table->boolean('is_visible')->default(true)->after('description');

            // Online visibility (tampil_online) — defaults to false (conservative)
            $table->boolean('is_online_visible')->default(false)->after('is_visible');

            // Quotas (kuota, kuota_jkn)
            $table->unsignedSmallInteger('quota')->default(0)->after('is_online_visible');
            $table->unsignedSmallInteger('jkn_quota')->default(0)->after('quota');

            // Legacy warehouse reference — stored as raw integer until Warehouse master is built.
            // NOT a foreign key. Will be linked to warehouses.id when that table is created.
            // Nullable: existing records that have no warehouse mapping are safe.
            $table->unsignedBigInteger('legacy_default_warehouse_id')->nullable()->after('jkn_quota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polyclinics', function (Blueprint $table) {
            $table->dropColumn([
                'service_type',
                'description',
                'is_visible',
                'is_online_visible',
                'quota',
                'jkn_quota',
                'legacy_default_warehouse_id',
            ]);
        });
    }
};
