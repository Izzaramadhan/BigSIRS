<?php

namespace App\Models\MasterData;

use App\Models\TariffComponent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
