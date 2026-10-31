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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number', 100)->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('title')->nullable();
            $table->string('type', 50)->default('Audit Rutin');
            $table->string('status', 50)->default('draft')->index(); // draft, counting, review, completed, cancelled
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('location_detail')->nullable();
            $table->string('officer_name')->nullable();
            $table->string('officer_role')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('counted_items')->default(0);
            $table->unsignedInteger('difference_count')->default(0);
            $table->decimal('total_difference_value', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['warehouse_id', 'status'], 'idx_opname_wh_status');
            $table->index(['start_date', 'status'], 'idx_opname_date_status');
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('warehouse_stock_id')->nullable()->constrained('warehouse_stocks')->nullOnDelete();
            $table->foreignId('medicine_id')->constrained('medicines')->restrictOnDelete();
            $table->string('medicine_name');
            $table->string('medicine_code', 100)->nullable();
            $table->string('unit', 50)->default('Unit');
            $table->string('batch_number', 100)->nullable();
            $table->date('expired_date')->nullable();
            $table->decimal('system_stock', 12, 2)->default(0);
            $table->decimal('physical_stock', 12, 2)->nullable();
            $table->decimal('difference', 12, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('difference_value', 15, 2)->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('counted_at')->nullable();

            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                $table->string('stock_batch_key', 191)->storedAs("COALESCE(batch_number, '__EMPTY__') || '#' || COALESCE(expired_date, '1970-01-01')");
            } else {
                $table->string('stock_batch_key', 191)->storedAs("CONCAT(COALESCE(`batch_number`, '__EMPTY__'), '#', COALESCE(`expired_date`, '1970-01-01'))");
            }

            $table->timestamps();

            $table->unique(['stock_opname_id', 'medicine_id', 'stock_batch_key'], 'uq_opname_med_batch');
            $table->index(['stock_opname_id', 'medicine_id'], 'idx_opname_med');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
    }
};

