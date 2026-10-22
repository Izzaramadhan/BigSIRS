<?php

namespace App\Models\MasterData;

use Database\Factories\MasterData\FloorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Floor extends Model
{
    /** @use HasFactory<FloorFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'floors';

    protected $fillable = [
        'building_id',
        'legacy_id',
        'name',
        'description',
    ];

    protected $casts = [
        'building_id' => 'integer',
        'legacy_id' => 'integer',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = "%{$term}%";

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', $term)
                ->orWhere('description', 'like', $term)
                ->orWhereHas('building', function (Builder $b) use ($term) {
                    $b->where('name', 'like', $term);
                });
        });
    }

    protected static function newFactory()
    {
        return FloorFactory::new();
    }
}
