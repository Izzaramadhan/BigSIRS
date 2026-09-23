<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcedureCategory extends Model
{
    /** @use HasFactory<\Database\Factories\ProcedureCategoryFactory> */
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
