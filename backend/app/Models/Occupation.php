<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Occupation extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function delete()
    {
        if ($this->employees()->exists()) {
            throw new \Exception('Cannot delete occupation because it is still used by employees.');
        }

        return parent::delete();
    }
}
