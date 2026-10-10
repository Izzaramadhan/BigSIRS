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
        Schema::table('medical_procedure_tariff_components', function (Blueprint $table) {
            $table->decimal('percentage_snapshot', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_procedure_tariff_components', function (Blueprint $table) {
            $table->decimal('percentage_snapshot', 5, 2)->change();
        });
    }
};
