<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_procedure_tariff_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->foreignId('medical_procedure_tariff_id')->constrained('medical_procedure_tariffs', 'id', 'mptc_mpt_id_foreign')->cascadeOnDelete();
            $table->foreignId('tariff_component_id')->constrained('tariff_components')->cascadeOnDelete();
            $table->decimal('percentage_snapshot', 5, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_procedure_tariff_components');
    }
};
