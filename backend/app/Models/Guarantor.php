<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\GuarantorType;

class Guarantor extends Model
{
    /** @use HasFactory<\Database\Factories\GuarantorFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'code',
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'type' => GuarantorType::class,
        'is_active' => 'boolean',
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
