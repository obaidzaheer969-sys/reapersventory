<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'wallet_balance' => (float) $this['wallet_balance'],
            'total_earned' => (float) $this['total_earned'],
            'total_withdrawn' => (float) $this['total_withdrawn'],
            'pending_withdrawals' => (int) $this['pending_withdrawals'],
            'total_deposited' => (float) $this['total_deposited'],
            'min_payout' => (float) $this['min_payout'],
            'max_payout' => (float) $this['max_payout'],
            'payout_cooldown_hours' => (int) $this['payout_cooldown_hours'],
            'payout_fee_percent' => (float) $this['payout_fee_percent'],
            'withdrawals_enabled' => (bool) $this['withdrawals_enabled'],
            'next_withdrawal_at' => $this['next_withdrawal_at']?->toISOString(),
            'recent_transactions' => TransactionResource::collection($this['recent_transactions']),
        ];
    }
}
