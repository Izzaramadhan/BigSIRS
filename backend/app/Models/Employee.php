<?php

namespace App\Models;

use App\Models\MasterData\Doctor;
use App\Models\MasterData\MedicalProcedure;
use App\Models\MasterData\Position;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
        'allergies',
        'religion',
        'marital_status',
        'address',
        'postal_code',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'phone',
        'signature_path',
        'education_id',
        'occupation_id',
        'position_id',
        'profession',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'birth_date' => 'date',
    ];

    protected $appends = [
        'has_signature',
        'signature_url',
    ];

    public function getHasSignatureAttribute(): bool
    {
        return ! empty($this->signature_path);
    }

    public function getSignatureUrlAttribute(): ?string
    {
        return $this->signature_path ? Storage::disk('public')->url($this->signature_path) : null;
    }

    public function procedures()
    {
        return $this->belongsToMany(MedicalProcedure::class, 'procedure_employee', 'employee_id', 'procedure_id')
            ->withTimestamps();
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class, 'employee_id');
    }

    public function education()
    {
        return $this->belongsTo(Education::class);
    }

    public function occupation()
    {
        return $this->belongsTo(Occupation::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
