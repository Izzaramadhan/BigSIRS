<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('icd9_cms', function (Blueprint $table) {
            $table->renameColumn('description', 'name');
        });

        Schema::table('icd9_cms', function (Blueprint $table) {
            $table->string('english_name')->nullable()->after('name');
            $table->text('description')->nullable()->after('english_name');
            $table->string('inacbg_code', 100)->nullable()->after('is_active');
            $table->text('inacbg_name')->nullable()->after('inacbg_code');
        });
    }

    public function down(): void
    {
        Schema::table('icd9_cms', function (Blueprint $table) {
            $table->dropColumn(['english_name', 'description', 'inacbg_code', 'inacbg_name']);
        });

        Schema::table('icd9_cms', function (Blueprint $table) {
            $table->renameColumn('name', 'description');
        });
    }
};
