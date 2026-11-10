<?php

namespace App\Models\Legacy;

use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LegacyMedicineRoute extends LegacyModel
{
    protected $table = 'ref_obat_jalur_masuk';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * MedicineRoute is enabled for controlled direct CRUD.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected $appends = [
        'legacy_id',
        'name',
        'object_code',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function getDeletedAtColumnName(): ?string
    {
        return 'delete_time';
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyMedicineRoute $model) {
            if (empty($model->create_time)) {
                $model->create_time = now()->format('Y-m-d H:i:s');
            }
            if ($model->status === null) {
                $model->status = 1;
            }
        });

        static::updating(function (LegacyMedicineRoute $model) {
            $model->update_time = now()->format('Y-m-d H:i:s');
        });
    }

    /**
     * Check if medicine route is referenced in medicines or transactions.
     */
    public function isReferencedInTransactions(): bool
    {
        $id = $this->id;
        if (! $id) {
            return false;
        }

        try {
            $conn = DB::connection('legacy');

            // 1. Check legacy ref_obat
            if ($conn->getSchemaBuilder()->hasTable('ref_obat')) {
                $exists = $conn->table('ref_obat')
                    ->where('id_obat_jalur_masuk', $id)
                    ->where(function ($q) {
                        $q->whereNull('deleted_at')
                            ->orWhere('deleted_at', '0000-00-00 00:00:00')
                            ->orWhere('deleted_at', '0000-00-00');
                    })
                    ->exists();

                if ($exists) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // fallback
        }

        // 2. Check bigsirs_dev Medicine
        try {
            if (class_exists(Medicine::class)) {
                if (Medicine::where('medicine_route_id', $id)->exists()) {
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
        $this->delete_time = now()->format('Y-m-d H:i:s');
        $this->status = 0;
        $this->update_time = now()->format('Y-m-d H:i:s');
        if ($userId) {
            $this->user_id = $userId;
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
        return (string) ($this->attributes['rute_obat'] ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['rute_obat'] = $value !== null ? trim((string) $value) : '';
    }

    public function getObjectCodeAttribute(): ?string
    {
        return $this->attributes['obj_id'] ?? null;
    }

    public function setObjectCodeAttribute($value): void
    {
        $this->attributes['obj_id'] = $value !== null ? trim((string) $value) : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    public function getCreatedAtAttribute(): ?string
    {
        return $this->attributes['create_time'] ?? null;
    }

    public function getUpdatedAtAttribute(): ?string
    {
        return $this->attributes['update_time'] ?? null;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('rute_obat', 'like', $term)
              ->orWhere('obj_id', 'like', $term);
        });
    }
}
