<?php

namespace App\Models\MasterData;

use App\Policies\LaboratoryItemPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(LaboratoryItemPolicy::class)]
class LaboratoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'name',
        'reference_value',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'legacy_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('reference_value', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%");
            });
        });
    }
}
