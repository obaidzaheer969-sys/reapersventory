<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || ($user->isBusinessOwner() && $user->business_id !== null);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || ($user->isBusinessOwner() && $user->business_id !== null);
    }

    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin()
            || ($user->isBusinessOwner()
                && $user->belongsToBusiness((int) $model->business_id));
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
