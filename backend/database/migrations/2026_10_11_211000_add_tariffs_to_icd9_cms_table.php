<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('icd9_cms', function (Blueprint $table) {
            if (! Schema::hasColumn('icd9_cms', 'class_1_tariff')) {
                $table->decimal('class_1_tariff', 15, 2)->nullable()->after('inacbg_name');
            }
            if (! Schema::hasColumn('icd9_cms', 'class_2_tariff')) {
                $table->decimal('class_2_tariff', 15, 2)->nullable()->after('class_1_tariff');
            }
            if (! Schema::hasColumn('icd9_cms', 'class_3_tariff')) {
                $table->decimal('class_3_tariff', 15, 2)->nullable()->after('class_2_tariff');
            }
        });
    }

    public function down(): void
    {
        Schema::table('icd9_cms', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('icd9_cms', 'class_1_tariff')) {
                $columnsToDrop[] = 'class_1_tariff';
            }
            if (Schema::hasColumn('icd9_cms', 'class_2_tariff')) {
                $columnsToDrop[] = 'class_2_tariff';
            }
            if (Schema::hasColumn('icd9_cms', 'class_3_tariff')) {
                $columnsToDrop[] = 'class_3_tariff';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
