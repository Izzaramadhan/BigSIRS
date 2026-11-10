<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegacyFollowUpHandling extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'ref_penanganan_lanjutan';

    protected $guarded = ['id'];

    protected $appends = ['name', 'code', 'is_active', 'legacy_id', 'inacbg_code'];

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

        static::creating(function (LegacyFollowUpHandling $model) {
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

        static::updating(function (LegacyFollowUpHandling $model) {
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
        return $this->attributes['kode'] ?? null;
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['kode'] = $value !== null ? strtoupper(trim($value)) : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    public function getInacbgCodeAttribute(): ?int
    {
        return isset($this->attributes['kode_inacbg']) ? (int) $this->attributes['kode_inacbg'] : null;
    }

    public function setInacbgCodeAttribute($value): void
    {
        $this->attributes['kode_inacbg'] = $value !== null ? (int) $value : null;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('nama', 'like', $term)
              ->orWhere('kode', 'like', $term);
        });
    }
}
