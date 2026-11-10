<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegacyDistrict extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'ref_kecamatan';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $appends = ['name', 'code', 'regency_id', 'is_active', 'legacy_id'];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return 'deleted_at';
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (LegacyDistrict $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if (empty($model->id_user)) {
                $model->id_user = auth()->id() ?? 1;
            }
            if (! isset($model->attributes['status'])) {
                $model->attributes['status'] = 1;
            }
            if (empty($model->id)) {
                if (! empty($model->kode_kecamatan)) {
                    $model->id = (string) $model->kode_kecamatan;
                }
            }
        });

        static::updating(function (LegacyDistrict $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
            if (auth()->check()) {
                $model->id_user = auth()->id();
            }
        });
    }

    public function getLegacyIdAttribute(): ?string
    {
        return isset($this->attributes['id']) ? (string) $this->attributes['id'] : null;
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
        return $this->attributes['kode_kecamatan'] ?? null;
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['kode_kecamatan'] = $value !== null ? trim($value) : null;
    }

    public function getRegencyIdAttribute(): ?string
    {
        return isset($this->attributes['id_kabupaten']) ? (string) $this->attributes['id_kabupaten'] : null;
    }

    public function setRegencyIdAttribute($value): void
    {
        $this->attributes['id_kabupaten'] = $value !== null ? (string) $value : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(LegacyRegency::class, 'id_kabupaten', 'id');
    }

    public function villages(): HasMany
    {
        return $this->hasMany(LegacyVillage::class, 'id_kecamatan', 'id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('nama', 'like', $term)
              ->orWhere('kode_kecamatan', 'like', $term);
        });
    }
}
