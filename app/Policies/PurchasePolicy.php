<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy extends BusinessOwnedPolicy
{
    public function receive(User $user, Purchase $purchase): bool
    {
        return $this->update($user, $purchase);
    }

    public function cancel(User $user, Purchase $purchase): bool
    {
        return $this->update($user, $purchase);
    }
}
