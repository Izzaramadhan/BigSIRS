<?php

namespace App\Models\Legacy;

use App\Support\LegacyAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LegacyIcd9 extends LegacyModel
{
    protected $table = 'ref_icd_9';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Unlocked for controlled legacy CRUD operations.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function getTable()
    {
        if (LegacyAccess::isAvailable('ref_icd_9')) {
            return 'ref_icd_9';
        }

        if (LegacyAccess::isAvailable('ref_icd9')) {
            return 'ref_icd9';
        }

        return $this->table ?? 'ref_icd_9';
    }

    protected $appends = [
        'code',
        'name',
        'english_name',
        'description',
        'is_active',
        'needs_review',
        'inacbg_code',
        'inacbg_name',
        'class_1_tariff',
        'class_2_tariff',
        'class_3_tariff',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyIcd9 $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if ($model->updated_at === null) {
                $model->updated_at = '0000-00-00 00:00:00';
            }
            if ($model->deleted_at === null) {
                $model->deleted_at = '0000-00-00 00:00:00';
            }
            if ($model->status === null) {
                $model->status = 1;
            }
            if ($model->id_user === null) {
                $model->id_user = 0;
            }
        });

        static::updating(function (LegacyIcd9 $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
        });
    }

    // Accessors
    public function getCodeAttribute(): string
    {
        return (string) ($this->attributes['kode'] ?? '');
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getEnglishNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? ($this->attributes['nama'] ?? null);
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi_inacbg'] ?? ($this->attributes['keterangan'] ?? null);
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function getNeedsReviewAttribute(): bool
    {
        return false;
    }

    public function getInacbgCodeAttribute(): ?string
    {
        return $this->attributes['kode_inacbg'] ?? null;
    }

    public function getInacbgNameAttribute(): ?string
    {
        return $this->attributes['deskripsi_inacbg'] ?? null;
    }

    public function getClass1TariffAttribute(): ?float
    {
        return isset($this->attributes['tarif_kelas1']) ? (float) $this->attributes['tarif_kelas1'] : null;
    }

    public function getClass2TariffAttribute(): ?float
    {
        return isset($this->attributes['tarif_kelas2']) ? (float) $this->attributes['tarif_kelas2'] : null;
    }

    public function getClass3TariffAttribute(): ?float
    {
        return isset($this->attributes['tarif_kelas3']) ? (float) $this->attributes['tarif_kelas3'] : null;
    }

    // Mutators
    public function setCodeAttribute($value): void
    {
        $this->attributes['kode'] = trim(strtoupper((string) $value));
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function setEnglishNameAttribute($value): void
    {
        if (array_key_exists('name', $this->attributes)) {
            $this->attributes['name'] = $value;
        } else {
            $this->attributes['nama'] = $value ?: ($this->attributes['nama'] ?? '');
        }
    }

    public function setDescriptionAttribute($value): void
    {
        if (array_key_exists('keterangan', $this->attributes)) {
            $this->attributes['keterangan'] = $value;
        } else {
            $this->attributes['deskripsi_inacbg'] = $value;
        }
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = $value ? 1 : 0;
    }

    public function setInacbgCodeAttribute($value): void
    {
        $this->attributes['kode_inacbg'] = $value;
    }

    public function setInacbgNameAttribute($value): void
    {
        $this->attributes['deskripsi_inacbg'] = $value;
    }

    public function setClass1TariffAttribute($value): void
    {
        $this->attributes['tarif_kelas1'] = $value !== null ? (float) $value : null;
    }

    public function setClass2TariffAttribute($value): void
    {
        $this->attributes['tarif_kelas2'] = $value !== null ? (float) $value : null;
    }

    public function setClass3TariffAttribute($value): void
    {
        $this->attributes['tarif_kelas3'] = $value !== null ? (float) $value : null;
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

    /**
     * Check if this ICD-9 record is referenced in patient/clinical transactions.
     */
    public function hasTransactionReferences(): bool
    {
        try {
            $conn = DB::connection('legacy');
            if ($conn->getSchemaBuilder()->hasTable('trx_icd_9')) {
                if ($conn->table('trx_icd_9')->where('id_icd_9', $this->id)->exists()) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable) {
            return false;
        }
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
