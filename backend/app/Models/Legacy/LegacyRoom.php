<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyRoom extends LegacyModel
{
    protected $table = 'ref_kamar';

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

    protected $appends = [
        'code',
        'name',
        'description',
        'is_active',
        'ward_id',
        'room_class_id',
        'total_beds',
        'occupied_beds',
        'empty_beds',
    ];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(LegacyWard::class, 'id_bangsal');
    }

    public function roomClass(): BelongsTo
    {
        return $this->belongsTo(LegacyRoomClass::class, 'id_kelas');
    }

    public function beds(): HasMany
    {
        return $this->hasMany(LegacyBed::class, 'id_kamar');
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['kode'] ?? null;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi'] ?? null;
    }

    public function getWardIdAttribute(): ?int
    {
        return isset($this->attributes['id_bangsal']) ? (int) $this->attributes['id_bangsal'] : null;
    }

    public function getRoomClassIdAttribute(): ?int
    {
        return isset($this->attributes['id_kelas']) ? (int) $this->attributes['id_kelas'] : null;
    }

    public function getTotalBedsAttribute(): int
    {
        return (int) ($this->attributes['total_bed'] ?? 0);
    }

    public function getOccupiedBedsAttribute(): int
    {
        return (int) ($this->attributes['total_bed_terpakai'] ?? 0);
    }

    public function getEmptyBedsAttribute(): int
    {
        return (int) ($this->attributes['total_bed_kosong'] ?? 0);
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
              ->orWhere('kode', 'like', $term)
              ->orWhere('deskripsi', 'like', $term);
        });
    }
}
