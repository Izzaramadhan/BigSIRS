<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LegacyMedicationSigna extends LegacyModel
{
    protected $table = 'ref_signa';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * MedicationSigna is enabled for controlled direct CRUD.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected $appends = [
        'legacy_id',
        'name',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyMedicationSigna $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if ($model->status === null) {
                $model->status = 1;
            }
        });

        static::updating(function (LegacyMedicationSigna $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
        });
    }

    /**
     * Check if medication signa is referenced in prescriptions or related transactions.
     */
    public function isReferencedInTransactions(): bool
    {
        $name = $this->nama;
        if (! $name) {
            return false;
        }

        try {
            $conn = DB::connection('legacy');

            // 1. Check trx_resep_detail
            if ($conn->getSchemaBuilder()->hasTable('trx_resep_detail')) {
                if ($conn->table('trx_resep_detail')->where('signa', $name)->exists()) {
                    return true;
                }
            }

            // 2. Check trx_racikan
            if ($conn->getSchemaBuilder()->hasTable('trx_racikan')) {
                if ($conn->table('trx_racikan')->where('signa', $name)->exists()) {
                    return true;
                }
            }

            // 3. Check trx_resep_surveilans
            if ($conn->getSchemaBuilder()->hasTable('trx_resep_surveilans')) {
                if ($conn->table('trx_resep_surveilans')->where('signa', $name)->exists()) {
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

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
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
        return $query->where('nama', 'like', $term);
    }
}
