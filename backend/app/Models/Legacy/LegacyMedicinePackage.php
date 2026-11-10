<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class LegacyMedicinePackage extends LegacyModel
{
    protected $table = 'ref_paket_obat';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * MedicinePackage is enabled for controlled direct CRUD.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected $appends = [
        'legacy_id',
        'name',
        'price',
        'quantity',
        'description',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyMedicinePackage $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if ($model->status === null) {
                $model->status = '1';
            }
        });

        static::updating(function (LegacyMedicinePackage $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(LegacyMedicinePackageItem::class, 'id_ref_paket_obat');
    }

    /**
     * Check if package is referenced in clinical/billing transactions.
     */
    public function isReferencedInTransactions(): bool
    {
        // Currently ref_paket_obat is only referenced by map_paket_obat
        return false;
    }

    /**
     * Perform safe legacy soft delete.
     */
    public function performSoftDelete(?int $userId = null): bool
    {
        $this->deleted_at = now()->format('Y-m-d H:i:s');
        $this->status = '0';
        $this->updated_at = now()->format('Y-m-d H:i:s');
        if ($userId) {
            $this->id_user = (string) $userId;
        }

        $saved = $this->save();

        if ($saved) {
            $this->items()->update([
                'status' => '0',
                'deleted_at' => now()->format('Y-m-d H:i:s'),
                'id_user' => (string) ($userId ?? 1),
            ]);
        }

        return $saved;
    }

    // Accessors & Mutators
    public function getLegacyIdAttribute(): ?int
    {
        return isset($this->attributes['id']) ? (int) $this->attributes['id'] : null;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nama'] = $value !== null ? trim((string) $value) : '';
    }

    public function getPriceAttribute(): ?float
    {
        return isset($this->attributes['harga']) ? (float) $this->attributes['harga'] : null;
    }

    public function setPriceAttribute($value): void
    {
        $this->attributes['harga'] = $value !== null ? round((float) $value) : null;
    }

    public function getQuantityAttribute(): ?int
    {
        return isset($this->attributes['jumlah']) ? (int) $this->attributes['jumlah'] : null;
    }

    public function setQuantityAttribute($value): void
    {
        $this->attributes['jumlah'] = $value !== null && $value !== '' ? (int) $value : null;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi'] ?? null;
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['deskripsi'] = $value !== null ? trim((string) $value) : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
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

