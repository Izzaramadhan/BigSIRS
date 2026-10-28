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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('invoice_number', 100)->unique();
            $table->string('reference_number', 100)->nullable()->index();
            $table->date('invoice_date')->index();
            $table->date('due_date')->nullable()->index();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('supplier_name', 255)->nullable();
            $table->string('supplier_city', 255)->nullable();
            $table->string('supplier_npwp', 100)->nullable();
            $table->string('status', 50)->default('Belum Lunas')->index();
            $table->string('tax_type', 50)->default('non_tax')->index();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->date('paid_at')->nullable();
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained('medicines')->nullOnDelete();
            $table->string('medicine_name', 255);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit', 100)->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->string('discount_type', 20)->default('percentage');
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->boolean('is_repack')->default(false);
            $table->decimal('margin', 8, 4)->default(0.2800);
            $table->string('batch_number', 100)->nullable();
            $table->date('expired_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
