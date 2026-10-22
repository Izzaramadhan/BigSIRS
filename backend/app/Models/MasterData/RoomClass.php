<?php

namespace App\Models\MasterData;

use Database\Factories\MasterData\RoomClassFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomClass extends Model
{
    /** @use HasFactory<RoomClassFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'room_classes';

    protected $fillable = [
        'legacy_id',
        'code',
        'name',
        'description',
    ];

    protected $casts = [
        'legacy_id' => 'integer',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'room_class_id');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = "%{$term}%";

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', $term)
                ->orWhere('code', 'like', $term)
                ->orWhere('description', 'like', $term);
        });
    }

    protected static function newFactory()
    {
        return RoomClassFactory::new();
    }
}
