<?php

namespace App\Enums;

enum SaleCustomerType: string
{
    case WalkIn = 'walk_in';
    case Online = 'online';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::WalkIn => 'Walk-in customer',
            self::Online => 'Online order',
            self::Customer => 'Customer',
        };
    }
}
