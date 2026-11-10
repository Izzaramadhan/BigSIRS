<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LegacyRadiologyGroup extends LegacyModel
{

    protected $table = 'ref_grup_rad';

    protected $guarded = [];

    protected $appends = [
        'name',
        'price',
        'interpretation_price',
        'is_active',
        'radiology_category_id',
        'radiology_type_id',
        'activity_type_id',
    ];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LegacyRadiologyCategory::class, 'id_kategori_rad');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LegacyRadiologyType::class, 'id_tipe_rad');
    }

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(LegacyActivityType::class, 'id_ref_jenis_kegiatan');
    }

    public function itemGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            LegacyRadiologyItemGroup::class,
            'map_grup_kelompok_rad',
            'id_grup_rad',
            'id_kelompok_rad'
        );
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getPriceAttribute(): float
    {
        return (float) ($this->attributes['harga'] ?? 0);
    }

    public function setPriceAttribute($value): void
    {
        $this->attributes['harga'] = $value;
    }

    public function getInterpretationPriceAttribute(): float
    {
        return (float) ($this->attributes['harga_interpretasi'] ?? 0);
    }

    public function setInterpretationPriceAttribute($value): void
    {
        $this->attributes['harga_interpretasi'] = $value;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = $value ? 1 : 0;
    }

    public function getRadiologyCategoryIdAttribute(): ?int
    {
        return isset($this->attributes['id_kategori_rad']) ? (int) $this->attributes['id_kategori_rad'] : null;
    }

    public function setRadiologyCategoryIdAttribute($value): void
    {
        $this->attributes['id_kategori_rad'] = $value;
    }

    public function getRadiologyTypeIdAttribute(): ?int
    {
        return isset($this->attributes['id_tipe_rad']) ? (int) $this->attributes['id_tipe_rad'] : null;
    }

    public function setRadiologyTypeIdAttribute($value): void
    {
        $this->attributes['id_tipe_rad'] = $value;
    }

    public function getActivityTypeIdAttribute(): ?int
    {
        return isset($this->attributes['id_ref_jenis_kegiatan']) ? (int) $this->attributes['id_ref_jenis_kegiatan'] : null;
    }

    public function setActivityTypeIdAttribute($value): void
    {
        $this->attributes['id_ref_jenis_kegiatan'] = $value;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where('nama', 'like', $term);
    }

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
}
