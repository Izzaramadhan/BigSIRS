<?php

namespace App\Policies\MasterData;

use App\Models\MasterData\MedicinePackage;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MedicinePackagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MedicinePackage $medicinePackage): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MedicinePackage $medicinePackage): bool
    {
        return true;
    }

    public function delete(User $user, MedicinePackage $medicinePackage): bool
    {
        return true;
    }

    public function restore(User $user, MedicinePackage $medicinePackage): bool
    {
        return true;
    }

    public function forceDelete(User $user, MedicinePackage $medicinePackage): bool
    {
        return true;
    }
}
