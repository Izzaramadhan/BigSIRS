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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')
                ->constrained('wards')
                ->restrictOnDelete();
            // class_id reference is prepared for future master data Kelas integration
            $table->unsignedBigInteger('class_id')->nullable()->comment('Reference to master data Kelas when implemented');
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index(['ward_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
