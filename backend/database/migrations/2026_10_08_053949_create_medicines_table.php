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
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('kfa_code')->nullable();
            $table->string('function')->nullable();
            $table->foreignId('medicine_unit_id')->nullable()->constrained('medicine_units')->nullOnDelete();
            $table->foreignId('medicine_category_id')->nullable()->constrained('medicine_categories')->nullOnDelete();
            $table->foreignId('medicine_classification_id')->nullable()->constrained('medicine_classifications')->nullOnDelete();
            $table->foreignId('medicine_route_id')->nullable()->constrained('medicine_routes')->nullOnDelete();
            $table->foreignId('generic_medicine_id')->nullable()->constrained('generic_medicines')->nullOnDelete();
            $table->text('description')->nullable();
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
        Schema::dropIfExists('medicines');
    }
};
