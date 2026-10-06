<?php

namespace App\Models;

use Database\Factories\ProcedureCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcedureCategory extends Model
{
    /** @use HasFactory<ProcedureCategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
