<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Business $business): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isBusinessOwner()
            && $user->belongsToBusiness($business->id)
            && $user->is_active
            && $business->is_active;
    }

    public function update(User $user, Business $business): bool
    {
        return $this->view($user, $business);
    }

    public function delete(User $user, Business $business): bool
    {
        return $user->isSuperAdmin();
    }
}
