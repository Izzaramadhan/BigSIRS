<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('national_id', 20)->nullable()->unique()->after('code');
            $table->string('ihs_number')->nullable()->after('national_id');
            $table->string('birth_place')->nullable()->after('name');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->enum('gender', ['L', 'P'])->nullable()->after('birth_date');
            $table->string('nationality')->nullable()->after('gender');
            $table->enum('blood_type', ['A', 'B', 'AB', 'O', 'Unknown'])->nullable()->after('nationality');
            $table->string('religion')->nullable()->after('blood_type');
            $table->string('marital_status')->nullable()->after('religion');
            $table->text('address')->nullable()->after('marital_status');
            $table->string('postal_code')->nullable()->after('address');
            $table->string('province_id', 10)->nullable()->after('postal_code');
            $table->string('city_id', 10)->nullable()->after('province_id');
            $table->string('district_id', 10)->nullable()->after('city_id');
            $table->string('village_id', 15)->nullable()->after('district_id');
            $table->string('phone')->nullable()->after('village_id');
            $table->unsignedBigInteger('education_id')->nullable()->after('phone');
            $table->unsignedBigInteger('occupation_id')->nullable()->after('education_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'national_id', 'ihs_number', 'birth_place', 'birth_date', 'gender',
                'nationality', 'blood_type', 'religion', 'marital_status',
                'address', 'postal_code', 'province_id', 'city_id', 'district_id',
                'village_id', 'phone', 'education_id', 'occupation_id'
            ]);
        });
    }
};
