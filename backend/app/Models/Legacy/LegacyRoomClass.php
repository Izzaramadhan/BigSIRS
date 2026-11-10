<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyRoomClass extends LegacyModel
{
    protected $table = 'ref_kelas';

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
        'name',
        'code',
        'description',
        'price',
        'is_active',
        'aplicare_code',
        'sirs_code',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(LegacyRoom::class, 'id_kelas');
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['kode'] ?? null;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi'] ?? null;
    }

    public function getPriceAttribute(): float
    {
        return (float) ($this->attributes['harga'] ?? 0);
    }

    public function getAplicareCodeAttribute(): ?string
    {
        return $this->attributes['kode_aplicares'] ?? null;
    }

    public function getSirsCodeAttribute(): ?string
    {
        return $this->attributes['kode_sirs'] ?? null;
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
              ->orWhere('deskripsi', 'like', $term)
              ->orWhere('kode', 'like', $term);
        });
    }
}
