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
        Schema::create('goods_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('issue_number', 100)->unique();
            $table->dateTime('issue_date')->index();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('location_detail', 255)->nullable();
            $table->string('officer_name', 255);
            $table->foreignId('officer_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('officer_role', 255)->nullable();
            $table->string('purpose', 100)->default('Kebutuhan Internal');
            $table->string('purpose_code', 50)->nullable()->index();
            $table->string('reference_number', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('draft')->index(); // draft, completed, cancelled
            $table->decimal('total_quantity', 12, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'issue_date']);
            $table->index(['status', 'issue_date']);
        });

        Schema::create('goods_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_issue_id')->constrained('goods_issues')->cascadeOnDelete();
            $table->foreignId('warehouse_stock_id')->nullable()->constrained('warehouse_stocks')->nullOnDelete();
            $table->foreignId('medicine_id')->constrained('medicines')->restrictOnDelete();
            $table->string('medicine_name', 255);
            $table->string('batch_number', 100)->nullable();
            $table->date('expired_date')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 100)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['goods_issue_id', 'medicine_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_issue_items');
        Schema::dropIfExists('goods_issues');
    }
};

