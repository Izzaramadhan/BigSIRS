<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyBed extends LegacyModel
{
    protected $table = 'ref_bed';

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

    protected $appends = ['name', 'description', 'room_id', 'is_active', 'status_display'];

    public function room(): BelongsTo
    {
        return $this->belongsTo(LegacyRoom::class, 'id_kamar');
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi'] ?? null;
    }

    public function getRoomIdAttribute(): ?int
    {
        return isset($this->attributes['id_kamar']) ? (int) $this->attributes['id_kamar'] : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function getStatusDisplayAttribute(): string
    {
        return ($this->attributes['tersedia'] ?? 'kosong') === 'terpakai' ? 'Terisi' : 'Kosong';
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
