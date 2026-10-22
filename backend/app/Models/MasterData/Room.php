<?php

namespace App\Models\MasterData;

use Database\Factories\MasterData\RoomFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'rooms';

    protected $fillable = [
        'ward_id',
        'class_id',
        'legacy_id',
        'code',
        'name',
        'description',
    ];

    protected $casts = [
        'ward_id' => 'integer',
        'class_id' => 'integer',
        'legacy_id' => 'integer',
    ];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_id');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = "%{$term}%";

        return $query->where(function (Builder $q) use ($term) {
            $q->where('code', 'like', $term)
                ->orWhere('name', 'like', $term)
                ->orWhere('description', 'like', $term)
                ->orWhereHas('ward', function (Builder $w) use ($term) {
                    $w->where('name', 'like', $term)
                        ->orWhereHas('floor', function (Builder $f) use ($term) {
                            $f->where('name', 'like', $term)
                                ->orWhereHas('building', function (Builder $b) use ($term) {
                                    $b->where('name', 'like', $term);
                                });
                        });
                });
        });
    }

    protected static function newFactory()
    {
        return RoomFactory::new();
    }
}
