<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PayoutStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WalletResource;
use App\Models\User;
use App\Services\PayoutService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __invoke(Request $request, PayoutService $payouts): WalletResource
    {
        /** @var User $user */
        $user = $request->user();

        $cooldownHours = max(0, (int) config('earning.payout_cooldown_hours', 24));
        $latestPayout = $user->payoutRequests()
            ->latest('created_at')
            ->latest('id')
            ->first();

        $nextWithdrawalAt = null;

        if ($cooldownHours > 0 && $latestPayout?->created_at !== null) {
            $candidate = $latestPayout->created_at->copy()->addHours($cooldownHours);
            $nextWithdrawalAt = $candidate->isFuture() ? $candidate : null;
        }

        return new WalletResource([
            'wallet_balance' => (float) $user->wallet_balance,
            'total_earned' => $user->totalEarned(),
            'total_withdrawn' => $user->totalWithdrawn(),
            'pending_withdrawals' => $user->payoutRequests()
                ->where('status', PayoutStatus::Pending->value)
                ->count(),
            'total_deposited' => $user->totalDeposited(),
            'min_payout' => (float) config('earning.min_payout', 50),
            'max_payout' => (float) config('earning.max_payout', 250),
            'payout_cooldown_hours' => $cooldownHours,
            'payout_fee_percent' => (float) config('earning.payout_admin_fee_percent', 10),
            'withdrawals_enabled' => $payouts->areEnabled(),
            'next_withdrawal_at' => $nextWithdrawalAt,
            'recent_transactions' => $user->walletTransactions()->latest()->limit(10)->get(),
        ]);
    }
}
