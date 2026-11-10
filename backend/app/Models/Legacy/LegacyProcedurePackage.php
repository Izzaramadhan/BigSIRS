<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyProcedurePackage extends LegacyModel
{
    protected $table = 'ref_paket_tindakan';

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
        'is_active',
        'total_amount',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(LegacyProcedurePackageItem::class, 'id_ref_paket_tindakan');
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function getTotalAmountAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(function ($item) {
                return (float) ($item->procedure?->attributes['harga'] ?? 0);
            });
        }

        return 0;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('nama', 'like', "%{$term}%")
                ->orWhereHas('items.procedure', function (Builder $pq) use ($term) {
                    $pq->where('nama', 'like', "%{$term}%")
                        ->orWhere('kode', 'like', "%{$term}%");
                });
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '1');
    }
}

