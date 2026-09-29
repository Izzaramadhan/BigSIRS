<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Icd10Code extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'icd10_codes';

    protected $fillable = [
        'legacy_id',
        'code',
        'name',
        'english_name',
        'description',
        'is_medical_history',
        'is_active',
        'inacbg_code',
        'inacbg_name',
        'class_1_tariff',
        'class_2_tariff',
        'class_3_tariff',
    ];

    protected $casts = [
        'is_medical_history' => 'boolean',
        'is_active' => 'boolean',
        'class_1_tariff' => 'decimal:2',
        'class_2_tariff' => 'decimal:2',
        'class_3_tariff' => 'decimal:2',
    ];
}
