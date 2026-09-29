<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\TariffComponent;

class MedicalProcedureTariffComponent extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function medicalProcedureTariff(): BelongsTo
    {
        return $this->belongsTo(MedicalProcedureTariff::class);
    }

    public function tariffComponent(): BelongsTo
    {
        return $this->belongsTo(TariffComponent::class);
    }
}
