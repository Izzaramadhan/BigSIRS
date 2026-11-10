<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegacyLaboratoryGroup extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'ref_grup_lab';

    protected $guarded = [];

    protected $appends = [
        'name',
        'description',
        'price',
        'is_active',
        'laboratory_category_id',
    ];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LegacyLaboratoryCategory::class, 'id_kategori_lab');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(
            LegacyLaboratoryItem::class,
            'map_grup_item_lab',
            'id_grup',
            'id_item'
        );
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

    public function getPriceAttribute(): float
    {
        return (float) ($this->attributes['harga'] ?? 0);
    }

    public function setPriceAttribute($value): void
    {
        $this->attributes['harga'] = $value;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = $value ? '1' : '0';
    }

    public function getLaboratoryCategoryIdAttribute(): ?int
    {
        return isset($this->attributes['id_kategori_lab']) ? (int) $this->attributes['id_kategori_lab'] : null;
    }

    public function setLaboratoryCategoryIdAttribute($value): void
    {
        $this->attributes['id_kategori_lab'] = $value;
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
