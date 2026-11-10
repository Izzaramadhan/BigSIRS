<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyStockMutation extends LegacyModel
{
    protected $table = 'lgs_mutasi';

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
        'mutation_number',
        'mutationNumber',
        'mutation_date',
        'mutationDate',
        'source_warehouse_id',
        'sourceWarehouseId',
        'target_warehouse_id',
        'targetWarehouseId',
        'destination_warehouse_id',
        'destinationWarehouseId',
        'notes',
        'status',
        'is_active',
    ];

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(LegacyWarehouse::class, 'id_gudang_asal');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(LegacyWarehouse::class, 'id_gudang_tujuan');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->targetWarehouse();
    }

    public function items(): HasMany
    {
        return $this->hasMany(LegacyStockMutationItem::class, 'id_mutasi');
    }

    public function getMutationNumberAttribute(): string
    {
        $dateStr = ! empty($this->attributes['tanggal']) ? date('Ymd', strtotime($this->attributes['tanggal'])) : date('Ymd');
        return 'MUT-' . $dateStr . '-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getMutationNumberCamelAttribute(): string
    {
        return $this->getMutationNumberAttribute();
    }

    public function getMutationDateAttribute(): ?string
    {
        return $this->attributes['tanggal'] ?? null;
    }

    public function getMutationDateCamelAttribute(): ?string
    {
        return $this->getMutationDateAttribute();
    }

    public function getSourceWarehouseIdAttribute(): ?int
    {
        return isset($this->attributes['id_gudang_asal']) ? (int) $this->attributes['id_gudang_asal'] : null;
    }

    public function getSourceWarehouseIdCamelAttribute(): ?int
    {
        return $this->getSourceWarehouseIdAttribute();
    }

    public function getTargetWarehouseIdAttribute(): ?int
    {
        return isset($this->attributes['id_gudang_tujuan']) ? (int) $this->attributes['id_gudang_tujuan'] : null;
    }

    public function getTargetWarehouseIdCamelAttribute(): ?int
    {
        return $this->getTargetWarehouseIdAttribute();
    }

    public function getDestinationWarehouseIdAttribute(): ?int
    {
        return $this->getTargetWarehouseIdAttribute();
    }

    public function getDestinationWarehouseIdCamelAttribute(): ?int
    {
        return $this->getTargetWarehouseIdAttribute();
    }

    public function getNotesAttribute(): ?string
    {
        return $this->attributes['note'] ?? null;
    }

    public function getStatusAttribute(): string
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1' ? 'completed' : 'cancelled';
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
            $q->where('note', 'like', $term)
              ->orWhereHas('sourceWarehouse', fn ($w) => $w->where('nama', 'like', $term))
              ->orWhereHas('targetWarehouse', fn ($w) => $w->where('nama', 'like', $term));
        });
    }
}
