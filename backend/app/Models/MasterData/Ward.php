<?php

namespace App\Models\MasterData;

use Database\Factories\MasterData\WardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ward extends Model
{
    /** @use HasFactory<WardFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'wards';

    protected $fillable = [
        'floor_id',
        'legacy_id',
        'name',
        'description',
    ];

    protected $casts = [
        'floor_id' => 'integer',
        'legacy_id' => 'integer',
    ];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'floor_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'ward_id');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = "%{$term}%";

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', $term)
                ->orWhere('description', 'like', $term)
                ->orWhereHas('floor', function (Builder $f) use ($term) {
                    $f->where('name', 'like', $term)
                        ->orWhereHas('building', function (Builder $b) use ($term) {
                            $b->where('name', 'like', $term);
                        });
                });
        });
    }

    protected static function newFactory()
    {
        return WardFactory::new();
    }
}
