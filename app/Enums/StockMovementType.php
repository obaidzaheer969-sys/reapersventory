<?php

namespace App\Enums;

enum StockMovementType: string
{
    case PurchaseIn = 'purchase_in';
    case SaleOut = 'sale_out';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case ReturnIn = 'return_in';
    case ReturnOut = 'return_out';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';

    public function label(): string
    {
        return match ($this) {
            self::PurchaseIn => 'Purchase in',
            self::SaleOut => 'Sale out',
            self::AdjustmentIn => 'Adjustment in',
            self::AdjustmentOut => 'Adjustment out',
            self::ReturnIn => 'Return in',
            self::ReturnOut => 'Return out',
            self::TransferIn => 'Transfer in',
            self::TransferOut => 'Transfer out',
        };
    }

    public function isInbound(): bool
    {
        return in_array($this, [
            self::PurchaseIn,
            self::AdjustmentIn,
            self::ReturnIn,
            self::TransferIn,
        ], true);
    }
}
