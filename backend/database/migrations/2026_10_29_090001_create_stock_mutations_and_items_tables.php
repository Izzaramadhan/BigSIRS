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
        Schema::create('stock_mutations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('mutation_number', 100)->unique();
            $table->dateTime('mutation_date')->index();
            $table->foreignId('source_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('target_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('validated')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['source_warehouse_id', 'mutation_date']);
            $table->index(['target_warehouse_id', 'mutation_date']);
        });

        Schema::create('stock_mutation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_mutation_id')->constrained('stock_mutations')->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained('medicines')->restrictOnDelete();
            $table->string('medicine_name', 255);
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 100)->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->date('expired_date')->nullable();
            $table->timestamps();

            $table->index(['stock_mutation_id', 'medicine_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_mutation_items');
        Schema::dropIfExists('stock_mutations');
    }
};

