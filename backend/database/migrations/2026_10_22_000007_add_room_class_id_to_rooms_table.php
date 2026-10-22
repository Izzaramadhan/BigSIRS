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
        Schema::table('rooms', function (Blueprint $table) {
            if (! Schema::hasColumn('rooms', 'room_class_id')) {
                $table->foreignId('room_class_id')
                    ->nullable()
                    ->after('ward_id')
                    ->constrained('room_classes')
                    ->restrictOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasColumn('rooms', 'room_class_id')) {
                $table->dropForeign(['room_class_id']);
                $table->dropColumn('room_class_id');
            }
        });
    }
};
