<?php

namespace App\Policies\Logistics;

use App\Models\User;

class StockOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, mixed $stock): bool
    {
        return $user !== null;
    }

    public function create(User $user): bool
    {
        return $user !== null;
    }

    public function update(User $user, mixed $stock): bool
    {
        return $user !== null;
    }

    public function delete(User $user, mixed $stock): bool
    {
        return $user !== null;
    }
}
