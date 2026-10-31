<?php

namespace App\Policies\Logistics;

use App\Models\Logistics\StockMutation;
use App\Models\User;

class StockMutationPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * TODO: Restrict by role/permission once hospital RBAC module is fully integrated.
     */
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can view the model.
     *
     * TODO: Check if user has permission to access source/target warehouse.
     */
    public function view(User $user, StockMutation $mutation): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can create models.
     *
     * TODO: Check if user has logistics operator or pharmacist permission.
     */
    public function create(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can update the model.
     * Validated mutations cannot be modified to preserve stock audit trail integrity.
     */
    public function update(User $user, StockMutation $mutation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * Validated mutations cannot be deleted to preserve stock audit trail integrity.
     */
    public function delete(User $user, StockMutation $mutation): bool
    {
        return false;
    }
}

