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
        Schema::create('radiology_groups', function (Blueprint $table) {
            $table->id();
            $table->integer('legacy_id')->nullable()->unique();
            $table->foreignId('radiology_category_id')->nullable()->constrained('radiology_categories')->nullOnDelete();
            $table->foreignId('radiology_type_id')->nullable()->constrained('radiology_types')->nullOnDelete();
            $table->foreignId('activity_type_id')->nullable()->constrained('activity_types')->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('interpretation_price', 15, 2)->default(0);
            $table->string('loinc_code')->nullable();
            $table->string('loinc_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radiology_groups');
    }
};
