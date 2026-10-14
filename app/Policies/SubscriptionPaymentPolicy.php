<?php

namespace App\Policies;

use App\Models\SubscriptionPayment;
use App\Models\User;

class SubscriptionPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isBusinessOwner();
    }

    public function view(User $user, SubscriptionPayment $payment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isBusinessOwner() && $user->belongsToBusiness($payment->business_id);
    }

    public function create(User $user): bool
    {
        return $user->isBusinessOwner() && $user->business_id !== null;
    }

    public function approve(User $user, SubscriptionPayment $payment): bool
    {
        return $user->isSuperAdmin();
    }

    public function reject(User $user, SubscriptionPayment $payment): bool
    {
        return $user->isSuperAdmin();
    }
}
