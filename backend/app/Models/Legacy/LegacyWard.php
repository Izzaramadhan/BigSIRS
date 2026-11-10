<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyWard extends LegacyModel
{
    protected $table = 'ref_bangsal';

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

    protected $appends = ['name', 'description', 'floor_id', 'is_active'];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(LegacyFloor::class, 'id_lantai');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(LegacyRoom::class, 'id_bangsal');
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi'] ?? null;
    }

    public function getFloorIdAttribute(): ?int
    {
        return isset($this->attributes['id_lantai']) ? (int) $this->attributes['id_lantai'] : null;
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
