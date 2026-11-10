<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LegacySupplier extends LegacyModel
{
    protected $table = 'ref_supplier';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Supplier is enabled for controlled direct CRUD.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected $appends = ['id', 'legacy_id', 'name', 'phone', 'address', 'is_active'];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacySupplier $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if ($model->status === null) {
                $model->status = 1;
            }
        });

        static::updating(function (LegacySupplier $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
        });
    }

    /**
     * Check if supplier is referenced in invoices or transactions.
     */
    public function isReferencedInTransactions(): bool
    {
        $id = $this->id;
        if (! $id) {
            return false;
        }

        try {
            $conn = DB::connection('legacy');

            // Check purchase invoices (lgs_faktur)
            if ($conn->getSchemaBuilder()->hasTable('lgs_faktur')) {
                if ($conn->table('lgs_faktur')->where('id_supplier', $id)->exists()) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // fallback
        }

        return false;
    }

    /**
     * Perform safe legacy soft delete.
     */
    public function performSoftDelete(?int $userId = null): bool
    {
        $this->deleted_at = now()->format('Y-m-d H:i:s');
        $this->status = 0;
        $this->updated_at = now()->format('Y-m-d H:i:s');
        if ($userId) {
            $this->id_user = $userId;
        }

        return $this->save();
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

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['telp'] ?? null;
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['telp'] = $value !== null ? trim((string) $value) : null;
    }

    public function getAddressAttribute(): ?string
    {
        return $this->attributes['alamat'] ?? null;
    }

    public function setAddressAttribute($value): void
    {
        $this->attributes['alamat'] = $value !== null ? trim((string) $value) : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((int) ($this->attributes['status'] ?? 1)) === 1;
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
            $q->where('nama', 'like', $term)
              ->orWhere('telp', 'like', $term)
              ->orWhere('alamat', 'like', $term);
        });
    }
}
