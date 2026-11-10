<?php

namespace App\Policies\MasterData;

use App\Models\User;

class GenericMedicinePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, mixed $genericMedicine = null): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, mixed $genericMedicine = null): bool
    {
        return true;
    }

    public function delete(User $user, mixed $genericMedicine = null): bool
    {
        return true;
    }

    public function restore(User $user, mixed $genericMedicine = null): bool
    {
        return true;
    }

    public function forceDelete(User $user, mixed $genericMedicine = null): bool
    {
        return true;
    }
}

