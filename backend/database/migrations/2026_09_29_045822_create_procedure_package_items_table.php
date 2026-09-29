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
        Schema::create('procedure_package_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('legacy_id')->nullable();
            $table->foreignId('procedure_package_id')->constrained('procedure_packages')->cascadeOnDelete();
            $table->foreignId('medical_procedure_id')->constrained('medical_procedures')->cascadeOnDelete();
            $table->foreignId('medical_procedure_tariff_id')->nullable()->constrained('medical_procedure_tariffs')->nullOnDelete();
            $table->decimal('unit_amount', 15, 2)->default(0);
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_package_items');
    }
};
