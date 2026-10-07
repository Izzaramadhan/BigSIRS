<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LetterType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'letter_types';

    protected $fillable = [
        'legacy_id',
        'name',
        'description',
        'legacy_resource',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
