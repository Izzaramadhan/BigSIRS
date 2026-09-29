<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_procedure_tariffs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->foreignId('medical_procedure_id')->constrained('medical_procedures')->cascadeOnDelete();
            $table->foreignId('tariff_type_id')->constrained('tariff_types')->cascadeOnDelete();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_procedure_tariffs');
    }
};
