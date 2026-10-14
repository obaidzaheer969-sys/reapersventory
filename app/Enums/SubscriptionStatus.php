<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Grace = 'grace';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Trial',
            self::Active => 'Active',
            self::PastDue => 'Past due',
            self::Grace => 'Grace period',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    public function allowsAccess(): bool
    {
        return in_array($this, [self::Trial, self::Active, self::Grace], true);
    }
}
