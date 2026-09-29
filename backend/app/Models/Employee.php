<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\MasterData\MedicalProcedure;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'code',
        'name',
        'profession',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function procedures()
    {
        return $this->belongsToMany(MedicalProcedure::class, 'procedure_employee', 'employee_id', 'procedure_id')
                    ->withTimestamps();
    }
}
