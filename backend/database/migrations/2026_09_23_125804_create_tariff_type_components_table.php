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
        Schema::create('tariff_type_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('tariff_type_id')->constrained('tariff_types')->cascadeOnDelete();
            $table->foreignId('tariff_component_id')->constrained('tariff_components')->restrictOnDelete();
            $table->decimal('percentage', 10, 2);
            $table->boolean('needs_review')->default(false);
            $table->timestamps();
            
            // We do not add unique constraint on (tariff_type_id, tariff_component_id) 
            // to allow duplicate relations from legacy data.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariff_type_components');
    }
};
