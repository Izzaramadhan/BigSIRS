<?php

namespace App\Policies;

use App\Models\LetterType;
use App\Models\User;

class LetterTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LetterType $letterType): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true; // Simplified for this project
    }

    public function update(User $user, LetterType $letterType): bool
    {
        return true;
    }

    public function delete(User $user, LetterType $letterType): bool
    {
        return true;
    }
}
