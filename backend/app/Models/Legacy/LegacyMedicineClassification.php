<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;

class LegacyMedicineClassification extends LegacyModel
{
    protected $table = 'ref_obat_golongan';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $appends = [
        'legacy_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    public function getLegacyIdAttribute(): ?int
    {
        return isset($this->attributes['id']) ? (int) $this->attributes['id'] : null;
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['kode'] ?? null;
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['kode'] = $value !== null ? trim((string) $value) : null;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['name'] ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value !== null ? trim((string) $value) : '';
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['description'] ?? null;
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = $value !== null ? trim((string) $value) : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', $term)
              ->orWhere('kode', 'like', $term)
              ->orWhere('description', 'like', $term);
        });
    }
}

