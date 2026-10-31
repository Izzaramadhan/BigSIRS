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
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('transaction_number', 100)->unique();
            $table->dateTime('transaction_date')->index();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('customer_name', 255)->default('Walk-in Customer');
            $table->string('customer_type', 50)->default('Umum'); // Umum, Pasien, Staf RS
            $table->string('cashier_name', 255)->default('Petugas Kasir');
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_method', 50)->default('Tunai'); // Tunai, QRIS, Debit BCA, Debit Mandiri, dll.
            $table->decimal('total_quantity', 12, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->string('status', 50)->default('completed')->index(); // draft, completed, cancelled
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'transaction_date']);
            $table->index(['status', 'transaction_date']);
        });

        Schema::create('pos_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
            $table->foreignId('warehouse_stock_id')->nullable()->constrained('warehouse_stocks')->nullOnDelete();
            $table->foreignId('medicine_id')->constrained('medicines')->restrictOnDelete();
            $table->string('medicine_name', 255);
            $table->string('batch_number', 100)->nullable();
            $table->date('expired_date')->nullable();
            $table->string('unit', 100)->nullable();
            $table->decimal('quantity', 12, 2);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['pos_sale_id', 'medicine_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
    }
};

