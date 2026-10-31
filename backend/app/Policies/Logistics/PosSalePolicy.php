<?php

namespace App\Policies\Logistics;

use App\Models\Logistics\PosSale;
use App\Models\User;

class PosSalePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PosSale $posSale): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can update the model (not cancelled).
     */
    public function update(User $user, PosSale $posSale): bool
    {
        return $user !== null && ! $posSale->isCancelled();
    }

    /**
     * Determine whether the user can cancel the model (only completed or draft, not already cancelled).
     */
    public function cancel(User $user, PosSale $posSale): bool
    {
        return $user !== null && ! $posSale->isCancelled();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PosSale $posSale): bool
    {
        return $user !== null && $posSale->isDraft();
    }
}

