<?php

namespace App\Models\MasterData;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'employee_id',
        'specialization_id',
        'str_number',
        'sip_number',
        'sip_valid_until',
        'bpjs_dpjp_code',
        'ihs_number',
        'signature_path',
        'is_active',
    ];

    protected $casts = [
        'sip_valid_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function specialization()
    {
        return $this->belongsTo(Specialization::class, 'specialization_id');
    }
}
