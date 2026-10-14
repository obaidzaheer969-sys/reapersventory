<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy extends BusinessOwnedPolicy
{
    public function pay(User $user, Sale $sale): bool
    {
        return $this->update($user, $sale);
    }

    public function returnItems(User $user, Sale $sale): bool
    {
        return $this->update($user, $sale);
    }
}
