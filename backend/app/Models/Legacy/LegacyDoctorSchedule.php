<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyDoctorSchedule extends LegacyModel
{
    protected $table = 'trx_jadwal_dokter';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $guarded = ['id'];

    public static array $dayMap = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    public static array $dayNameMap = [
        'senin' => 1,
        'selasa' => 2,
        'rabu' => 3,
        'kamis' => 4,
        'jumat' => 5,
        'sabtu' => 6,
        'minggu' => 7,
    ];

    /**
     * DoctorSchedule is enabled for controlled direct CRUD.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return null; // trx_jadwal_dokter has DEFAULT CURRENT_TIMESTAMP on deleted_at
    }

    protected $appends = [
        'doctor_id',
        'polyclinic_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_holiday',
        'online_quota',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('active', function (Builder $builder) {
            $table = $builder->getModel()->getTable();
            $builder->where("{$table}.status", '1');
        });

        static::creating(function (LegacyDoctorSchedule $model) {
            if (empty($model->created_at)) {
                $model->created_at = now()->format('Y-m-d H:i:s');
            }
            if (empty($model->updated_at)) {
                $model->updated_at = now()->format('Y-m-d H:i:s');
            }
            if (empty($model->deleted_at)) {
                $model->deleted_at = now()->format('Y-m-d H:i:s');
            }
            if ($model->status === null) {
                $model->status = '1';
            }
            if ($model->libur === null) {
                $model->libur = 0;
            }
            if ($model->kuota_online === null) {
                $model->kuota_online = 0;
            }
            if (! empty($model->hari_praktik) && empty($model->index_hari)) {
                $lower = strtolower(trim((string) $model->hari_praktik));
                $model->index_hari = static::$dayNameMap[$lower] ?? null;
            }
        });

        static::updating(function (LegacyDoctorSchedule $model) {
            $model->updated_at = now()->format('Y-m-d H:i:s');
            if (! empty($model->hari_praktik)) {
                $lower = strtolower(trim((string) $model->hari_praktik));
                $model->index_hari = static::$dayNameMap[$lower] ?? $model->index_hari;
            }
        });
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(LegacyDoctor::class, 'id_dokter');
    }

    public function polyclinic(): BelongsTo
    {
        return $this->belongsTo(LegacyPolyclinic::class, 'id_poliklinik');
    }

    public function performSoftDelete(?int $userId = null): bool
    {
        $this->status = '0';
        $this->deleted_at = now()->format('Y-m-d H:i:s');
        $this->updated_at = now()->format('Y-m-d H:i:s');
        if ($userId) {
            $this->id_user = $userId;
        }

        return $this->save();
    }

    // Accessors & Mutators
    public function getDoctorIdAttribute(): ?int
    {
        return isset($this->attributes['id_dokter']) ? (int) $this->attributes['id_dokter'] : null;
    }

    public function setDoctorIdAttribute($value): void
    {
        $this->attributes['id_dokter'] = $value;
    }

    public function getPolyclinicIdAttribute(): ?int
    {
        return isset($this->attributes['id_poliklinik']) ? (int) $this->attributes['id_poliklinik'] : null;
    }

    public function setPolyclinicIdAttribute($value): void
    {
        $this->attributes['id_poliklinik'] = $value;
    }

    public function getDayOfWeekAttribute(): ?int
    {
        $val = $this->attributes['hari_praktik'] ?? null;
        if (is_numeric($val)) {
            return (int) $val;
        }
        if (! $val) {
            return isset($this->attributes['index_hari']) ? (int) $this->attributes['index_hari'] : null;
        }

        $lower = strtolower(trim((string) $val));
        return static::$dayNameMap[$lower] ?? (isset($this->attributes['index_hari']) ? (int) $this->attributes['index_hari'] : null);
    }

    public function setDayOfWeekAttribute($value): void
    {
        if (is_numeric($value)) {
            $intVal = (int) $value;
            $this->attributes['index_hari'] = $intVal;
            $this->attributes['hari_praktik'] = static::$dayMap[$intVal] ?? 'Senin';
        } elseif (is_string($value)) {
            $lower = strtolower(trim($value));
            $intVal = static::$dayNameMap[$lower] ?? 1;
            $this->attributes['index_hari'] = $intVal;
            $this->attributes['hari_praktik'] = static::$dayMap[$intVal] ?? ucfirst($value);
        }
    }

    public function getStartTimeAttribute(): ?string
    {
        $val = $this->attributes['jam_mulai'] ?? null;
        return $val ? substr((string) $val, 0, 5) : null;
    }

    public function setStartTimeAttribute($value): void
    {
        $this->attributes['jam_mulai'] = $value;
    }

    public function getEndTimeAttribute(): ?string
    {
        $val = $this->attributes['jam_selesai'] ?? null;
        return $val ? substr((string) $val, 0, 5) : null;
    }

    public function setEndTimeAttribute($value): void
    {
        $this->attributes['jam_selesai'] = $value;
    }

    public function getIsHolidayAttribute(): bool
    {
        return ((string) ($this->attributes['libur'] ?? '0')) === '1';
    }

    public function setIsHolidayAttribute($value): void
    {
        $this->attributes['libur'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    public function getOnlineQuotaAttribute(): int
    {
        return (int) ($this->attributes['kuota_online'] ?? 0);
    }

    public function setOnlineQuotaAttribute($value): void
    {
        $this->attributes['kuota_online'] = (int) $value;
    }

    public function getIsActiveAttribute(): bool
    {
        return ((string) ($this->attributes['status'] ?? '1')) === '1';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }

    public function getCreatedAtAttribute(): ?string
    {
        return $this->attributes['created_at'] ?? null;
    }

    public function getUpdatedAtAttribute(): ?string
    {
        return $this->attributes['updated_at'] ?? null;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = "%{$term}%";
        return $query->where(function ($q) use ($term) {
            $q->where('hari_praktik', 'like', $term)
              ->orWhereHas('doctor.person', function ($sub) use ($term) {
                  $sub->where('nama', 'like', $term);
              })
              ->orWhereHas('polyclinic', function ($sub) use ($term) {
                  $sub->where('nama', 'like', $term);
              });
        });
    }
}
