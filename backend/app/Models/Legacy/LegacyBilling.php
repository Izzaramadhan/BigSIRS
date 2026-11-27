<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyBilling extends LegacyModel
{
    protected $table = 'trx_billing';

    protected $primaryKey = 'id';

    /**
     * Strictly read-only in Fase 2B.19A until payment business rules are approved.
     */
    public function isReadOnly(): bool
    {
        return true;
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(LegacyVisit::class, 'id_visit', 'id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(LegacyAdmission::class, 'id_admisi', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(LegacyBillingDetail::class, 'id_billing', 'id');
    }
}
