<?php

namespace App\Policies\MasterData;

use App\Models\MasterData\RadiologyItemGroup;
use App\Models\User;

class RadiologyItemGroupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RadiologyItemGroup $radiologyItemGroup): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RadiologyItemGroup $radiologyItemGroup): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RadiologyItemGroup $radiologyItemGroup): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RadiologyItemGroup $radiologyItemGroup): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RadiologyItemGroup $radiologyItemGroup): bool
    {
        return true;
    }
}
