<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegacyLaboratoryCategory extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'ref_kategori_lab';

    protected $guarded = [];

    protected $appends = ['name', 'description', 'type', 'is_active'];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi'] ?? null;
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['deskripsi'] = $value;
    }

    public function getTypeAttribute(): ?string
    {
        return $this->attributes['tipe'] ?? null;
    }

    public function setTypeAttribute($value): void
    {
        $this->attributes['tipe'] = $value;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = $value ? '1' : '0';
    }

    public function groups(): HasMany
    {
        return $this->hasMany(LegacyLaboratoryGroup::class, 'id_kategori_lab');
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
