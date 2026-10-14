<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy extends BusinessOwnedPolicy
{
    public function download(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
