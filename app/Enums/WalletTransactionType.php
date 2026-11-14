<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case TaskReward = 'task_reward';
    case PayoutRequest = 'payout_request';
    case PayoutRefund = 'payout_refund';
    case Bonus = 'bonus';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::TaskReward => 'Task reward',
            self::PayoutRequest => 'Payout requested',
            self::PayoutRefund => 'Payout refunded',
            self::Bonus => 'Bonus',
            self::Adjustment => 'Adjustment',
        };
    }

    public function isCredit(): bool
    {
        return match ($this) {
            self::PayoutRequest => false,
            default => true,
        };
    }
}
