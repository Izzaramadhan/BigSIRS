<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TariffType;

class MedicalProcedureTariff extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function medicalProcedure(): BelongsTo
    {
        return $this->belongsTo(MedicalProcedure::class);
    }

    public function tariffType(): BelongsTo
    {
        return $this->belongsTo(TariffType::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(MedicalProcedureTariffComponent::class);
    }
}
