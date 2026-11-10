<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class LegacyModel extends Model
{
    /**
     * The database connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'legacy';

    /**
     * Disable default timestamp management by Eloquent
     * to avoid issues with legacy date formats (e.g., '0000-00-00 00:00:00').
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Guarded attributes.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Determine if this model is read-only.
     * By default, legacy models are strictly read-only to safeguard historical data.
     * Audited models (e.g. LegacyPolyclinic) override this to return false.
     */
    public function isReadOnly(): bool
    {
        return true;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Global scope to filter out soft-deleted legacy records
        static::addGlobalScope('notDeleted', function (Builder $builder) {
            $model = $builder->getModel();
            $table = $model->getTable();
            $deleteColumn = $model->getDeletedAtColumnName();

            if ($deleteColumn) {
                $builder->where(function ($query) use ($table, $deleteColumn) {
                    $query->whereNull("{$table}.{$deleteColumn}")
                        ->orWhere("{$table}.{$deleteColumn}", '0000-00-00 00:00:00')
                        ->orWhere("{$table}.{$deleteColumn}", '0000-00-00');
                });
            }
        });

        // Strict read-only safeguard for legacy database unless explicitly unlocked
        static::saving(function (LegacyModel $model) {
            if ($model->isReadOnly()) {
                throw new \RuntimeException('Operasi tulis ke database legacy dinonaktifkan untuk menjaga integritas data sumber.');
            }
        });

        static::deleting(function (LegacyModel $model) {
            if ($model->isReadOnly()) {
                throw new \RuntimeException('Operasi hapus ke database legacy dinonaktifkan untuk menjaga integritas data sumber.');
            }
        });
    }

    public function getIdAttribute(): ?int
    {
        return isset($this->attributes['id']) ? (int) $this->attributes['id'] : null;
    }

    /**
     * Get the column name used for soft deletes, or null if none.
     */
    public function getDeletedAtColumnName(): ?string
    {
        return 'deleted_at';
    }

    /**
     * Scope for active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        $table = $this->getTable();
        return $query->where(function ($q) use ($table) {
            $q->where("{$table}.status", '1')
                ->orWhere("{$table}.status", 1);
        });
    }
}
