<?php

namespace App\Services;

use App\Enums\PayoutStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Models\PayoutAccount;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Notifications\PayoutRequestedAdminNotification;
use App\Notifications\PayoutStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayoutService
{
    public function __construct(
        private readonly WalletService $wallet,
    ) {}

    public function requestPayout(User $user, PayoutAccount $account, float $amount): PayoutRequest
    {
        if ($account->user_id !== $user->id) {
            abort(403, 'This payout account does not belong to you.');
        }

        $minimum = (float) config('earning.min_payout', 500);

        if ($amount < $minimum) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum payout PKR '.number_format($minimum, 0).' hai.',
            ]);
        }

        $payout = DB::transaction(function () use ($user, $account, $amount) {
            $payout = PayoutRequest::query()->create([
                'user_id' => $user->id,
                'payout_account_id' => $account->id,
                'provider' => $account->provider,
                'account_name' => $account->account_name,
                'account_number' => $account->account_number,
                'amount' => round($amount, 2),
                'status' => PayoutStatus::Pending,
            ]);

            $this->wallet->debit(
                $user,
                $amount,
                WalletTransactionType::PayoutRequest,
                'Payout request #'.$payout->id.' ('.$account->provider->label().')',
                $payout,
            );

            return $payout;
        });

        $this->notifyAdmins($payout);

        return $payout;
    }

    public function markPaid(PayoutRequest $payout, User $admin, ?string $notes = null): PayoutRequest
    {
        $this->ensureAdmin($admin);

        $payout = DB::transaction(function () use ($payout, $admin, $notes) {
            /** @var PayoutRequest $payout */
            $payout = PayoutRequest::query()->whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($payout->status === PayoutStatus::Paid) {
                return $payout;
            }

            if ($payout->status !== PayoutStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Sirf pending payout ko paid mark kiya ja sakta hai.',
                ]);
            }

            $payout->forceFill([
                'status' => PayoutStatus::Paid,
                'processed_at' => now(),
                'processed_by' => $admin->id,
                'admin_notes' => $notes ?? $payout->admin_notes,
            ])->save();

            return $payout;
        });

        $payout->user?->notify(new PayoutStatusNotification($payout));

        return $payout;
    }

    public function reject(PayoutRequest $payout, User $admin, string $reason): PayoutRequest
    {
        $this->ensureAdmin($admin);

        $payout = DB::transaction(function () use ($payout, $admin, $reason) {
            /** @var PayoutRequest $payout */
            $payout = PayoutRequest::query()->whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($payout->status === PayoutStatus::Rejected) {
                return $payout;
            }

            if ($payout->status !== PayoutStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Sirf pending payout reject ki ja sakti hai.',
                ]);
            }

            $payout->forceFill([
                'status' => PayoutStatus::Rejected,
                'processed_at' => now(),
                'processed_by' => $admin->id,
                'admin_notes' => $reason,
            ])->save();

            $this->wallet->credit(
                $payout->user,
                (float) $payout->amount,
                WalletTransactionType::PayoutRefund,
                'Payout #'.$payout->id.' rejected — amount refunded',
                $payout,
            );

            return $payout;
        });

        $payout->user?->notify(new PayoutStatusNotification($payout));

        return $payout;
    }

    private function notifyAdmins(PayoutRequest $payout): void
    {
        User::query()
            ->where('role', UserRole::SuperAdmin->value)
            ->where('is_active', true)
            ->get()
            ->each(fn (User $admin) => $admin->notify(new PayoutRequestedAdminNotification($payout)));
    }

    private function ensureAdmin(User $admin): void
    {
        abort_unless($admin->isSuperAdmin(), 403, 'Only Super Admin can process payouts.');
    }
}
