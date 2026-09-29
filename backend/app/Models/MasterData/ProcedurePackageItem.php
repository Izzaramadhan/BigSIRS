<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcedurePackageItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'procedure_package_id',
        'medical_procedure_id',
        'medical_procedure_tariff_id',
        'unit_amount',
        'subtotal_amount',
        'sort_order',
        'quantity',
    ];

    protected $casts = [
        'unit_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function package()
    {
        return $this->belongsTo(ProcedurePackage::class, 'procedure_package_id');
    }

    public function procedure()
    {
        return $this->belongsTo(MedicalProcedure::class, 'medical_procedure_id');
    }

    public function tariff()
    {
        return $this->belongsTo(MedicalProcedureTariff::class, 'medical_procedure_tariff_id');
    }
}
