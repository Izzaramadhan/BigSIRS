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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained('medicines')->cascadeOnDelete();
            $table->foreignId('stock_mutation_id')->nullable()->constrained('stock_mutations')->nullOnDelete();
            $table->foreignId('stock_mutation_item_id')->nullable()->constrained('stock_mutation_items')->nullOnDelete();
            $table->string('movement_type', 50)->default('mutation')->index();
            $table->string('direction', 10)->index(); // IN or OUT
            $table->decimal('quantity', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('batch_number', 100)->nullable();
            $table->date('expired_date')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'medicine_id', 'occurred_at'], 'idx_wh_med_occurred');
            $table->index(['stock_mutation_id', 'direction'], 'idx_mutation_direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

