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
        Schema::table('room_classes', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->default(0)->after('name');
            $table->string('aplicare_code', 50)->nullable()->after('description');
            $table->string('sirs_code', 50)->nullable()->after('aplicare_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_classes', function (Blueprint $table) {
            $table->dropColumn(['price', 'aplicare_code', 'sirs_code']);
        });
    }
};
