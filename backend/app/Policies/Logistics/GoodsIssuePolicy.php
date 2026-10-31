<?php

namespace App\Policies\Logistics;

use App\Models\Logistics\GoodsIssue;
use App\Models\User;

class GoodsIssuePolicy
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
    public function view(User $user, GoodsIssue $goodsIssue): bool
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
     * Determine whether the user can update the model (only draft).
     */
    public function update(User $user, GoodsIssue $goodsIssue): bool
    {
        return $user !== null && $goodsIssue->isDraft();
    }

    /**
     * Determine whether the user can finalize the model (only draft).
     */
    public function finalize(User $user, GoodsIssue $goodsIssue): bool
    {
        return $user !== null && $goodsIssue->isDraft();
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, GoodsIssue $goodsIssue): bool
    {
        return $user !== null && ! $goodsIssue->isCancelled();
    }

    /**
     * Determine whether the user can delete the model (only draft).
     */
    public function delete(User $user, GoodsIssue $goodsIssue): bool
    {
        return $user !== null && $goodsIssue->isDraft();
    }
}

