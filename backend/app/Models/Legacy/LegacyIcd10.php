<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LegacyIcd10 extends LegacyModel
{
    protected $table = 'ref_diagnosa';
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

    protected $appends = [
        'name',
        'english_name',
        'description',
        'is_medical_history',
        'is_active',
        'inacbg_code',
        'inacbg_name',
        'class_1_tariff',
        'class_2_tariff',
        'class_3_tariff',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyIcd10 $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if (empty($model->updated_at)) {
                $model->updated_at = now()->format('Y-m-d H:i:s');
            }
            if ($model->deleted_at === null) {
                $model->deleted_at = '0000-00-00 00:00:00';
            }
            if ($model->status === null) {
                $model->status = '1';
            }
            if ($model->riwayat_penyakit === null) {
                $model->riwayat_penyakit = '0';
            }
            if (empty($model->code_root) && !empty($model->code)) {
                $model->code_root = explode('.', (string) $model->code)[0];
            }
        });

        static::updating(function (LegacyIcd10 $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
            if (empty($model->code_root) && !empty($model->code)) {
                $model->code_root = explode('.', (string) $model->code)[0];
            }
        });
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getEnglishNameAttribute(): ?string
    {
        return $this->attributes['nama_en'] ?? null;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi'] ?? null;
    }

    public function getIsMedicalHistoryAttribute(): bool
    {
        return ((string) ($this->attributes['riwayat_penyakit'] ?? '0')) === '1';
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
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
    public function setNameAttribute($value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function setEnglishNameAttribute($value): void
    {
        $this->attributes['nama_en'] = $value;
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['deskripsi'] = $value;
    }

    public function setIsMedicalHistoryAttribute($value): void
    {
        $this->attributes['riwayat_penyakit'] = $value ? '1' : '0';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = $value ? '1' : '0';
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
        $this->status = '0';
        $this->updated_at = now()->format('Y-m-d H:i:s');
        if ($userId) {
            $this->id_user = $userId;
        }

        return $this->save();
    }

    /**
     * Check if this ICD-10 record is referenced in patient/clinical transactions.
     */
    public function hasTransactionReferences(): bool
    {
        try {
            $conn = DB::connection('legacy');
            $checks = [
                'trx_diagnosa' => 'id_diagnosa',
                'trx_diagnosa_surveilans' => 'id_diagnosa',
                'trx_riwayat_penyakit' => 'id_diagnosa',
            ];

            foreach ($checks as $table => $column) {
                if ($conn->getSchemaBuilder()->hasTable($table)) {
                    if ($conn->table($table)->where($column, $this->id)->exists()) {
                        return true;
                    }
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
            $q->where('code', 'like', $term)
              ->orWhere('nama', 'like', $term)
              ->orWhere('nama_en', 'like', $term);
        });
    }
}
