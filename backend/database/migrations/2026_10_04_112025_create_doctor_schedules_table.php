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
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->foreignId('polyclinic_id')->constrained('polyclinics');

            // ISO-8601 day of week: 1 = Monday, 7 = Sunday
            $table->tinyInteger('day_of_week')->comment('1=Monday, ..., 7=Sunday');

            $table->time('start_time');
            $table->time('end_time');

            $table->boolean('is_holiday')->default(false);
            $table->integer('online_quota')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Allow searching and filtering
            $table->index(['doctor_id', 'day_of_week']);
            $table->index(['polyclinic_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
