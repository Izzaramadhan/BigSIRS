<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaboratoryGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'laboratory_category_id',
        'name',
        'description',
        'price',
        'is_active',
    ];

    protected $casts = [
        'legacy_id' => 'integer',
        'laboratory_category_id' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LaboratoryCategory::class, 'laboratory_category_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(LaboratoryItem::class, 'laboratory_group_items', 'laboratory_group_id', 'laboratory_item_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        });
    }
}
