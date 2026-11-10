<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyFloor extends LegacyModel
{
    protected $table = 'ref_lantai';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return 'deleted_at';
    }

    protected $appends = ['name', 'description', 'building_id', 'is_active'];

    public function building(): BelongsTo
    {
        return $this->belongsTo(LegacyBuilding::class, 'id_gedung');
    }

    public function wards(): HasMany
    {
        return $this->hasMany(LegacyWard::class, 'id_lantai');
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi'] ?? null;
    }

    public function getBuildingIdAttribute(): ?int
    {
        return isset($this->attributes['id_gedung']) ? (int) $this->attributes['id_gedung'] : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('nama', 'like', $term)
              ->orWhere('deskripsi', 'like', $term);
        });
    }
}
