<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case BusinessOwner = 'business_owner';
    case Earner = 'earner';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::BusinessOwner => 'Business Owner',
            self::Earner => 'Earner',
        };
    }
}
