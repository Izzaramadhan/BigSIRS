<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_procedure_report_group', function (Blueprint $table) {
            $table->foreignId('medical_procedure_id')->constrained('medical_procedures', 'id', 'mprg_mp_id_foreign')->cascadeOnDelete();
            $table->foreignId('report_group_id')->constrained('report_groups')->cascadeOnDelete();
            $table->primary(['medical_procedure_id', 'report_group_id'], 'mprg_mp_id_rg_id_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_procedure_report_group');
    }
};
