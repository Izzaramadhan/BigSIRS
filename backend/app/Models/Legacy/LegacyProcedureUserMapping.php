<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyProcedureUserMapping extends LegacyModel
{
    protected $table = 'map_ref_tindakan_user';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getDeletedAtColumnName(): ?string
    {
        return 'deleted_at';
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(LegacyMedicalProcedure::class, 'id_ref_tindakan');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(LegacyEmployee::class, 'id_aktor');
    }
}

