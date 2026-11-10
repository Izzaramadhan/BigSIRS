<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;

class LegacySpecialization extends LegacyModel
{
    protected $table = 'ref_spesialisasi';

    protected $appends = ['name', 'code', 'is_active'];

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama_spesialisasi'] ?? '');
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['kode'] ?? null;
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
            $q->where('nama_spesialisasi', 'like', $term)
              ->orWhere('kode', 'like', $term);
        });
    }
}

