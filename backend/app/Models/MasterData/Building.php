<?php

namespace App\Models\MasterData;

use Database\Factories\MasterData\BuildingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'buildings';

    protected $fillable = [
        'legacy_id',
        'name',
        'description',
    ];

    protected $casts = [
        'legacy_id' => 'integer',
    ];

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = "%{$term}%";

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', $term)
                ->orWhere('description', 'like', $term);
        });
    }

    protected static function newFactory()
    {
        return BuildingFactory::new();
    }
}
