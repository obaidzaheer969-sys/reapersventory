<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBusinessOwner() && $user->business_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->isBusinessOwner() && $user->business_id !== null;
    }

    public function update(User $user, User $model): bool
    {
        return $user->isBusinessOwner()
            && $user->belongsToBusiness((int) $model->business_id);
    }
}
