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
        Schema::create('radiology_group_item_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radiology_group_id')->constrained('radiology_groups')->cascadeOnDelete();
            $table->foreignId('radiology_item_group_id')->constrained('radiology_item_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['radiology_group_id', 'radiology_item_group_id'], 'group_item_group_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radiology_group_item_groups');
    }
};
