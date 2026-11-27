<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyBillingDetail extends LegacyModel
{
    protected $table = 'trx_billing_detail';

    protected $primaryKey = 'id';

    /**
     * Strictly read-only in Fase 2B.19A until payment business rules are approved.
     */
    public function isReadOnly(): bool
    {
        return true;
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(LegacyBilling::class, 'id_billing', 'id');
    }
}
