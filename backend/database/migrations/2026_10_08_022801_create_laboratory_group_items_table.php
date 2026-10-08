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
        Schema::create('laboratory_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_group_id')->constrained('laboratory_groups')->cascadeOnDelete();
            $table->foreignId('laboratory_item_id')->constrained('laboratory_items')->restrictOnDelete();
            $table->integer('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['laboratory_group_id', 'laboratory_item_id'], 'lab_group_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratory_group_items');
    }
};
