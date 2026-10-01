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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->integer('legacy_id')->nullable()->index();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('specialization_id')->nullable()->constrained('specializations')->nullOnDelete();
            $table->string('str_number')->nullable();
            $table->string('sip_number')->nullable();
            $table->date('sip_valid_until')->nullable();
            $table->string('bpjs_dpjp_code')->nullable();
            $table->string('ihs_number')->nullable();
            $table->text('signature_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
