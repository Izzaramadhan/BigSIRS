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
        Schema::table('laboratory_groups', function (Blueprint $table) {
            $table->decimal('harga_jaspel', 15, 2)->nullable()->after('price');
            $table->string('loinc_code')->nullable()->after('is_active');
            $table->string('loinc_url')->nullable()->after('loinc_code');
            $table->string('snomed_code')->nullable()->after('loinc_url');
            $table->string('snomed_url')->nullable()->after('snomed_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_groups', function (Blueprint $table) {
            $table->dropColumn(['harga_jaspel', 'loinc_code', 'loinc_url', 'snomed_code', 'snomed_url']);
        });
    }
};
