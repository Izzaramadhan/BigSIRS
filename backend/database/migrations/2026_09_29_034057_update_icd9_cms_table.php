<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('icd9_cms', 'name')) {
            Schema::table('icd9_cms', function (Blueprint $table) {
                $table->renameColumn('name', 'description');
            });
        }
        if (Schema::hasColumn('icd9_cms', 'is_visible')) {
            Schema::table('icd9_cms', function (Blueprint $table) {
                $table->renameColumn('is_visible', 'is_active');
            });
        }
        if (!Schema::hasColumn('icd9_cms', 'needs_review')) {
            Schema::table('icd9_cms', function (Blueprint $table) {
                $table->boolean('needs_review')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::table('icd9_cms', function (Blueprint $table) {
            $table->renameColumn('description', 'name');
            $table->renameColumn('is_active', 'is_visible');
            $table->dropColumn('needs_review');
        });
    }
};
