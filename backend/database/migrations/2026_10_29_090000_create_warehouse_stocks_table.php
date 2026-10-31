<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained('medicines')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('batch_number', 100)->nullable();
            $table->date('expired_date')->nullable();

            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                $table->string('stock_batch_key', 191)->storedAs("COALESCE(batch_number, '__EMPTY__') || '#' || COALESCE(expired_date, '1970-01-01')");
            } else {
                $table->string('stock_batch_key', 191)->storedAs("CONCAT(COALESCE(`batch_number`, '__EMPTY__'), '#', COALESCE(`expired_date`, '1970-01-01'))");
            }

            $table->timestamps();

            $table->unique(['warehouse_id', 'medicine_id', 'stock_batch_key'], 'uq_warehouse_medicine_batch');
            $table->index(['warehouse_id', 'medicine_id'], 'idx_warehouse_medicine');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');
    }
};

