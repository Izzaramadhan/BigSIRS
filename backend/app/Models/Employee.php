<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\MasterData\MedicalProcedure;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'code',
        'national_id',
        'ihs_number',
        'name',
        'birth_place',
        'birth_date',
        'gender',
        'nationality',
        'blood_type',
        'religion',
        'marital_status',
        'address',
        'postal_code',
        'province_id',
        'city_id',
        'district_id',
        'village_id',
        'phone',
        'education_id',
        'occupation_id',
        'profession',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'birth_date' => 'date',
    ];

    public function procedures()
    {
        return $this->belongsToMany(MedicalProcedure::class, 'procedure_employee', 'employee_id', 'procedure_id')
                    ->withTimestamps();
    }

    public function doctor()
    {
        return $this->hasOne(\App\Models\MasterData\Doctor::class, 'employee_id');
    }

    public function education()
    {
        return $this->belongsTo(Education::class);
    }

    public function occupation()
    {
        return $this->belongsTo(Occupation::class);
    }
}
