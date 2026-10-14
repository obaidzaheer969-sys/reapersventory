<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isBusinessOwner();
    }

    public function view(User $user, Subscription $subscription): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isBusinessOwner() && $user->belongsToBusiness($subscription->business_id);
    }

    public function cancel(User $user, Subscription $subscription): bool
    {
        return $this->view($user, $subscription) && $user->isBusinessOwner();
    }
}
