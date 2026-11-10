<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;

class LegacyEducation extends LegacyModel
{

    protected $table = 'ref_pendidikan';

    protected $guarded = ['id'];

    protected $appends = ['name', 'is_active', 'legacy_id'];

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

        static::creating(function (LegacyEducation $model) {
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

        static::updating(function (LegacyEducation $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
            if (auth()->check()) {
                $model->id_user = auth()->id();
            }
        });
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

    public function delete()
    {
        return $this->performSoftDelete(auth()->id() ?? request()?->user()?->getLegacyUserId());
    }

    public function getLegacyIdAttribute(): ?int
    {
        return isset($this->attributes['id']) ? (int) $this->attributes['id'] : null;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['pendidikan'] ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['pendidikan'] = $value !== null ? trim($value) : null;
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
        return $query->where('pendidikan', 'like', $term);
    }
}
