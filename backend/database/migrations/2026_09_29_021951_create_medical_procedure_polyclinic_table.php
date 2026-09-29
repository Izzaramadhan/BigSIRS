<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_procedure_polyclinic', function (Blueprint $table) {
            $table->foreignId('medical_procedure_id')->constrained('medical_procedures')->cascadeOnDelete();
            $table->foreignId('polyclinic_id')->constrained('polyclinics')->cascadeOnDelete();
            $table->primary(['medical_procedure_id', 'polyclinic_id'], 'mpp_mp_id_p_id_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_procedure_polyclinic');
    }
};
