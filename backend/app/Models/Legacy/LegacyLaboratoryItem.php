<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;

class LegacyLaboratoryItem extends LegacyModel
{
    protected $table = 'ref_item_lab';

    protected $guarded = [];

    protected $appends = ['name', 'reference_value', 'unit', 'is_active'];

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

    public function getReferenceValueAttribute(): ?string
    {
        return $this->attributes['standar_normal'] ?? null;
    }

    public function setReferenceValueAttribute($value): void
    {
        $this->attributes['standar_normal'] = $value;
    }

    public function getUnitAttribute(): ?string
    {
        return $this->attributes['satuan'] ?? null;
    }

    public function setUnitAttribute($value): void
    {
        $this->attributes['satuan'] = $value;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = $value ? '1' : '0';
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('nama', 'like', $term)
              ->orWhere('standar_normal', 'like', $term)
              ->orWhere('satuan', 'like', $term);
        });
    }

    public function performSoftDelete(?int $userId = null): bool
    {
        $this->deleted_at = now()->format('Y-m-d H:i:s');
        $this->status = '0';
        $this->updated_at = now()->format('Y-m-d H:i:s');
        if ($userId) {
            $this->id_user = $userId;
        }

        return $this->save();
    }

    public function delete()
    {
        return $this->performSoftDelete(auth()->id() ?? request()?->user()?->getLegacyUserId());
    }
}
