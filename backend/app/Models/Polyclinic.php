<?php

namespace App\Models;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Polyclinic extends Model
{
    /** @use HasFactory<\Database\Factories\PolyclinicFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'parent_id',
        'code',
        'name',
        'service_type',
        'description',
        'is_visible',
        'is_online_visible',
        'quota',
        'jkn_quota',
        'bpjs_code',
        'satusehat_code',
        'is_active',
        // legacy_default_warehouse_id is managed by importer only
    ];

    protected $casts = [
        'service_type'      => ServiceType::class,
        'is_active'         => 'boolean',
        'is_visible'        => 'boolean',
        'is_online_visible' => 'boolean',
        'quota'             => 'integer',
        'jkn_quota'         => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Polyclinic::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Polyclinic::class, 'parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = "%{$term}%";
        return $query->where(function ($query) use ($term) {
            $query->where('code', 'like', $term)
                  ->orWhere('name', 'like', $term)
                  ->orWhere('bpjs_code', 'like', $term);
        });
    }
}
