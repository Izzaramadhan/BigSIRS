<?php

namespace App\Models;

use App\Enums\GuarantorType;
use Database\Factories\GuarantorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guarantor extends Model
{
    /** @use HasFactory<GuarantorFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'code',
        'name',
        'type',
        'is_active',
        'is_government',
        'inacbg_id',
    ];

    protected $casts = [
        'type' => GuarantorType::class,
        'is_active' => 'boolean',
        'is_government' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = "%{$term}%";

        return $query->where(function ($query) use ($term) {
            $query->where('code', 'like', $term)
                ->orWhere('name', 'like', $term);
        });
    }
}
