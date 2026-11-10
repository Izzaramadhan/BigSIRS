<?php

namespace App\Models\Legacy;

use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LegacyMedicineCategory extends LegacyModel
{
    protected $table = 'ref_kategori_obat';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * MedicineCategory is enabled for controlled direct CRUD.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    protected $appends = ['id', 'legacy_id', 'name', 'description', 'is_active'];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyMedicineCategory $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if ($model->status === null) {
                $model->status = '1';
            }
        });

        static::updating(function (LegacyMedicineCategory $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
        });
    }

    /**
     * Check if medicine category is referenced in medicines or dependent tables.
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
                    ->where('id_kategori_obat', $id)
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

            // 2. Check legacy ref_obat_ori if present
            if ($conn->getSchemaBuilder()->hasTable('ref_obat_ori')) {
                if ($conn->table('ref_obat_ori')->where('id_kategori_obat', $id)->exists()) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // fallback
        }

        // 3. Check bigsirs_dev Medicine if present
        try {
            if (class_exists(Medicine::class)) {
                if (Medicine::where('medicine_category_id', $id)->exists()) {
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
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nama'] = $value !== null ? trim((string) $value) : '';
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
