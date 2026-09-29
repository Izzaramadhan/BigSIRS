<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icd10_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('english_name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_medical_history')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('inacbg_code', 100)->nullable();
            $table->text('inacbg_name')->nullable();
            $table->decimal('class_1_tariff', 15, 2)->default(0);
            $table->decimal('class_2_tariff', 15, 2)->default(0);
            $table->decimal('class_3_tariff', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icd10_codes');
    }
};
