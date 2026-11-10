<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegacyRadiologyItem extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'ref_item_rad';

    protected $guarded = [];

    protected $appends = ['name', 'is_active', 'radiology_item_group_id'];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function itemGroup(): BelongsTo
    {
        return $this->belongsTo(LegacyRadiologyItemGroup::class, 'id_kelompok_item_rad');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(LegacyRadiologyItemGroup::class, 'id_kelompok_item_rad');
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = $value ? 1 : 0;
    }

    public function getRadiologyItemGroupIdAttribute(): ?int
    {
        return isset($this->attributes['id_kelompok_item_rad']) ? (int) $this->attributes['id_kelompok_item_rad'] : null;
    }

    public function setRadiologyItemGroupIdAttribute($value): void
    {
        $this->attributes['id_kelompok_item_rad'] = $value;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where('nama', 'like', $term);
    }
}
