<?php

namespace App\Models\Legacy;

use App\Models\Polyclinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LegacyWarehouse extends LegacyModel
{
    protected $table = 'ref_gudang';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Warehouse is enabled for controlled direct CRUD.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected $appends = ['id', 'legacy_id', 'code', 'name', 'description', 'is_active'];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyWarehouse $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if ($model->status === null) {
                $model->status = '1';
            }
            if ($model->kode === null) {
                $model->kode = '';
            }
        });

        static::updating(function (LegacyWarehouse $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
        });
    }

    /**
     * Check if warehouse is referenced in transactions or dependent tables.
     */
    public function isReferencedInTransactions(): bool
    {
        $id = $this->id;
        if (! $id) {
            return false;
        }

        try {
            $conn = DB::connection('legacy');

            // 1. Check stock log (lgs_log_stok)
            if ($conn->getSchemaBuilder()->hasTable('lgs_log_stok')) {
                if ($conn->table('lgs_log_stok')->where('id_gudang', $id)->exists()) {
                    return true;
                }
            }

            // 2. Check mutations as source or destination (lgs_mutasi)
            if ($conn->getSchemaBuilder()->hasTable('lgs_mutasi')) {
                if ($conn->table('lgs_mutasi')
                    ->where('id_gudang_asal', $id)
                    ->orWhere('id_gudang_tujuan', $id)
                    ->exists()) {
                    return true;
                }
            }

            // 3. Check stock opnames (lgs_stok_opname)
            if ($conn->getSchemaBuilder()->hasTable('lgs_stok_opname')) {
                if ($conn->table('lgs_stok_opname')->where('id_gudang', $id)->exists()) {
                    return true;
                }
            }

            // 4. Check polyclinics referencing this warehouse in legacy ref_poliklinik
            if ($conn->getSchemaBuilder()->hasTable('ref_poliklinik')) {
                $polyExists = $conn->table('ref_poliklinik')
                    ->where('id_gudang', $id)
                    ->where(function ($q) {
                        $q->whereNull('deleted_at')
                            ->orWhere('deleted_at', '0000-00-00 00:00:00')
                            ->orWhere('deleted_at', '0000-00-00');
                    })
                    ->exists();
                if ($polyExists) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // fallback
        }

        // 5. Also check bigsirs_dev Polyclinic if present
        try {
            if (class_exists(Polyclinic::class)) {
                $isUsedInPolyclinic = Polyclinic::where('warehouse_id', $id)
                    ->orWhere('legacy_default_warehouse_id', $id)
                    ->exists();
                if ($isUsedInPolyclinic) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // ignore
        }

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
        return (string) ($this->attributes['nama'] ?? $this->attributes['name'] ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nama'] = $value !== null ? trim((string) $value) : '';
    }

    public function getCodeAttribute(): ?string
    {
        $code = $this->attributes['kode'] ?? $this->attributes['code'] ?? null;
        if ($code !== null && $code !== '') {
            return (string) $code;
        }

        return 'GDG-' . ($this->attributes['id'] ?? 'NEW');
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['kode'] = $value !== null ? trim((string) $value) : null;
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
              ->orWhere('kode', 'like', $term)
              ->orWhere('deskripsi', 'like', $term);
        });
    }
}
