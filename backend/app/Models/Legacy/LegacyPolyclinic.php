<?php

namespace App\Models\Legacy;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class LegacyPolyclinic extends LegacyModel
{
    protected $table = 'ref_poliklinik';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Polyclinic is enabled for controlled read-write operations.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected $appends = [
        'id',
        'legacy_id',
        'code',
        'name',
        'service_type',
        'description',
        'is_visible',
        'is_online_visible',
        'quota',
        'jkn_quota',
        'bpjs_code',
        'is_active',
        'warehouse_id',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyPolyclinic $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if ($model->parent_id === null) {
                $model->parent_id = '';
            }
            if ($model->status_gudang === null) {
                $model->status_gudang = '0';
            }
            if ($model->tampil === null) {
                $model->tampil = '1';
            }
            if ($model->tampil_online === null) {
                $model->tampil_online = '0';
            }
            if ($model->status === null) {
                $model->status = '1';
            }
            if ($model->kuota === null) {
                $model->kuota = 0;
            }
            if ($model->kuota_jkn === null) {
                $model->kuota_jkn = 0;
            }
            if ($model->tampil_antrian === null) {
                $model->tampil_antrian = 0;
            }
        });

        static::updating(function (LegacyPolyclinic $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(LegacyWarehouse::class, 'id_gudang');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'kode');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'kode');
    }

    /**
     * Check if polyclinic has active sub-polyclinics.
     */
    public function hasActiveChildren(): bool
    {
        $code = (string) ($this->attributes['kode'] ?? '');
        if ($code === '') {
            return false;
        }

        return self::where('parent_id', $code)->exists();
    }

    /**
     * Check if polyclinic is referenced in transactional or mapping tables.
     */
    public function isReferencedInTransactions(): bool
    {
        $id = $this->id;
        if (! $id) {
            return false;
        }

        try {
            $conn = DB::connection('legacy');

            $checks = [
                'trx_visit' => 'id_poliklinik',
                'trx_jadwal_dokter' => 'id_poliklinik',
                'map_tarif_tindakan_poliklinik' => 'id_poliklinik',
                'trx_antrian' => 'id_poliklinik',
                'trx_pendaftaran_online' => 'id_poliklinik',
                'trx_admisi' => 'id_poliklinik_kontrol',
            ];

            foreach ($checks as $table => $column) {
                try {
                    if ($conn->getSchemaBuilder()->hasTable($table)) {
                        if ($conn->table($table)->where($column, $id)->exists()) {
                            return true;
                        }
                    }
                } catch (\Throwable) {
                    continue;
                }
            }

            return false;
        } catch (\Throwable) {
            return false;
        }
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

    // Accessors
    public function getLegacyIdAttribute(): ?int
    {
        return isset($this->attributes['id']) ? (int) $this->attributes['id'] : null;
    }

    public function getCodeAttribute(): string
    {
        return (string) ($this->attributes['kode'] ?? '');
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getServiceTypeAttribute(): ?ServiceType
    {
        $val = $this->attributes['jenis'] ?? null;
        if (! $val) {
            return null;
        }

        return ServiceType::tryFrom($val);
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi'] ?? null;
    }

    public function getIsVisibleAttribute(): bool
    {
        return ((string) ($this->attributes['tampil'] ?? '1')) === '1';
    }

    public function getIsOnlineVisibleAttribute(): bool
    {
        return ((string) ($this->attributes['tampil_online'] ?? '1')) === '1';
    }

    public function getQuotaAttribute(): int
    {
        return (int) ($this->attributes['kuota'] ?? 0);
    }

    public function getJknQuotaAttribute(): int
    {
        return (int) ($this->attributes['kuota_jkn'] ?? 0);
    }

    public function getBpjsCodeAttribute(): ?string
    {
        return $this->attributes['kode_bpjs'] ?? null;
    }

    public function getWarehouseIdAttribute(): ?int
    {
        return isset($this->attributes['id_gudang']) && (int) $this->attributes['id_gudang'] > 0
            ? (int) $this->attributes['id_gudang']
            : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    // Mutators
    public function setCodeAttribute($value): void
    {
        $this->attributes['kode'] = $value !== null ? strtoupper(trim($value)) : null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nama'] = $value !== null ? trim($value) : null;
    }

    public function setServiceTypeAttribute($value): void
    {
        $this->attributes['jenis'] = $value instanceof ServiceType ? $value->value : $value;
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['deskripsi'] = $value;
    }

    public function setIsVisibleAttribute($value): void
    {
        $this->attributes['tampil'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }

    public function setIsOnlineVisibleAttribute($value): void
    {
        $this->attributes['tampil_online'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }

    public function setQuotaAttribute($value): void
    {
        $this->attributes['kuota'] = (int) $value;
    }

    public function setJknQuotaAttribute($value): void
    {
        $this->attributes['kuota_jkn'] = (int) $value;
    }

    public function setBpjsCodeAttribute($value): void
    {
        $this->attributes['kode_bpjs'] = $value;
    }

    public function setWarehouseIdAttribute($value): void
    {
        $this->attributes['id_gudang'] = $value ? (int) $value : null;
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
            $q->where('kode', 'like', $term)
                ->orWhere('nama', 'like', $term);
        });
    }
}
