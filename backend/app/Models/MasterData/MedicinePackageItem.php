<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;

class MedicinePackageItem extends Model
{
    protected $fillable = [
        'legacy_id',
        'medicine_package_id',
        'medicine_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function package()
    {
        return $this->belongsTo(MedicinePackage::class, 'medicine_package_id');
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }
}
