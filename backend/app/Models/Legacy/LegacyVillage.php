<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyVillage extends LegacyModel
{
    protected $table = 'ref_kelurahan';

    protected $guarded = ['id'];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return null; // ref_kelurahan has timestamp in deleted_at from legacy dump; status column is used for filtering
    }

    protected $appends = ['name', 'code', 'district_id', 'is_active', 'legacy_id'];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyVillage $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if (empty($model->id_user)) {
                $model->id_user = auth()->id() ?? 1;
            }
            if (! isset($model->attributes['status'])) {
                $model->attributes['status'] = 1;
            }
        });

        static::updating(function (LegacyVillage $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
            if (auth()->check()) {
                $model->id_user = auth()->id();
            }
        });
    }

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
        $this->attributes['nama'] = $value !== null ? trim($value) : null;
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['kode_kelurahan'] ?? null;
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['kode_kelurahan'] = $value !== null ? trim($value) : null;
    }

    public function getDistrictIdAttribute(): ?string
    {
        return isset($this->attributes['id_kecamatan']) ? (string) $this->attributes['id_kecamatan'] : null;
    }

    public function setDistrictIdAttribute($value): void
    {
        $this->attributes['id_kecamatan'] = $value !== null ? (string) $value : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(LegacyDistrict::class, 'id_kecamatan', 'id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('nama', 'like', $term)
              ->orWhere('kode_kelurahan', 'like', $term);
        });
    }

    public function delete()
    {
        $this->status = 0;
        $this->deleted_at = now()->format('Y-m-d H:i:s');
        $this->updated_at = now()->format('Y-m-d H:i:s');
        return $this->save();
    }
}
