<?php

namespace App\Enums;

enum PlanFeatureKey: string
{
    case ProductsLimit = 'products_limit';
    case InvoicesLimit = 'invoices_limit';
    case WarehousesLimit = 'warehouses_limit';
    case UsersLimit = 'users_limit';

    public function label(): string
    {
        return match ($this) {
            self::ProductsLimit => 'Products',
            self::InvoicesLimit => 'Billing / invoices',
            self::WarehousesLimit => 'Warehouses',
            self::UsersLimit => 'Users',
        };
    }
}
