<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_procedures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('code')->nullable()->index();
            $table->string('name')->nullable();
            $table->foreignId('procedure_category_id')->nullable()->constrained('procedure_categories')->nullOnDelete();
            $table->foreignId('icd9_cm_id')->nullable()->constrained('icd9_cms')->nullOnDelete();
            $table->boolean('is_visible')->default(true);
            $table->boolean('needs_review')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_procedures');
    }
};
