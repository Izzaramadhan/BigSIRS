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
        Schema::create('radiology_categories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('legacy_id')->nullable()->unique()->comment('ID dari simrs_legacy');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('loinc_code')->nullable();
            $table->string('loinc_url')->nullable();
            $table->string('snomed_code')->nullable();
            $table->string('snomed_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radiology_categories');
    }
};
