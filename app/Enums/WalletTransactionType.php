<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case TaskReward = 'task_reward';
    case PayoutRequest = 'payout_request';
    case PayoutRefund = 'payout_refund';
    case CampaignFunding = 'campaign_funding';
    case CampaignRefund = 'campaign_refund';
    case CampaignAdminFee = 'campaign_admin_fee';
    case Deposit = 'deposit';
    case DepositAdminFee = 'deposit_admin_fee';
    case PayoutAdminFee = 'payout_admin_fee';
    case PayoutFeeReversal = 'payout_fee_reversal';
    case AdminCredit = 'admin_credit';
    case Bonus = 'bonus';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::TaskReward => 'Task reward',
            self::PayoutRequest => 'Payout requested',
            self::PayoutRefund => 'Payout refunded',
            self::CampaignFunding => 'Campaign payment',
            self::CampaignRefund => 'Campaign refund',
            self::CampaignAdminFee => 'Campaign admin fee',
            self::Deposit => 'Wallet deposit',
            self::DepositAdminFee => 'Deposit admin fee',
            self::PayoutAdminFee => 'Withdrawal admin fee',
            self::PayoutFeeReversal => 'Withdrawal fee refunded',
            self::AdminCredit => 'Admin credit',
            self::Bonus => 'Bonus',
            self::Adjustment => 'Adjustment',
        };
    }

    public function isCredit(): bool
    {
        return match ($this) {
            self::PayoutRequest, self::PayoutFeeReversal, self::CampaignFunding => false,
            default => true,
        };
    }
}
