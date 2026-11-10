<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegacyDietType extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'ref_jenis_diet';

    protected $guarded = ['id'];

    protected $appends = ['name', 'description', 'is_active', 'legacy_id'];

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

        static::creating(function (LegacyDietType $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if (empty($model->id_user)) {
                $model->id_user = auth()->id() ?? 1;
            }
            if (! isset($model->attributes['status'])) {
                $model->attributes['status'] = '1';
            }
        });

        static::updating(function (LegacyDietType $model) {
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

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['deskripsi'] ?? null;
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['deskripsi'] = $value;
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
