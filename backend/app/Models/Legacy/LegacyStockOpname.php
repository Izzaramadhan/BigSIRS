<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyStockOpname extends LegacyModel
{
    protected $table = 'lgs_stok_opname';

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
        'warehouse_id',
        'opname_date',
        'is_active',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(LegacyWarehouse::class, 'id_gudang');
    }

    public function details(): HasMany
    {
        return $this->hasMany(LegacyStockOpnameDetail::class, 'id_lgs_stok_opname');
    }

    public function getWarehouseIdAttribute(): ?int
    {
        return isset($this->attributes['id_gudang']) ? (int) $this->attributes['id_gudang'] : null;
    }

    public function getOpnameDateAttribute(): ?string
    {
        return $this->attributes['tanggal'] ?? null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) !== '0';
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->whereHas('warehouse', function ($sub) use ($term) {
            $sub->where('nama', 'like', $term);
        });
    }
}
