<?php

namespace App\Services;

use App\Enums\PayoutStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Models\PayoutAccount;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\PayoutRequestedAdminNotification;
use App\Notifications\PayoutStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayoutService
{
    public function __construct(
        private readonly WalletService $wallet,
    ) {}

    /**
     * @return array{gross: float, fee: float, net: float}
     */
    public function quote(float $amount): array
    {
        $gross = round($amount, 2);
        $percent = max(0.0, (float) config('earning.payout_admin_fee_percent', 10));
        $fee = round($gross * $percent / 100, 2);

        return [
            'gross' => $gross,
            'fee' => $fee,
            'net' => round(max(0, $gross - $fee), 2),
        ];
    }

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

        $cooldownHours = max(0, (int) config('earning.payout_cooldown_hours', 24));

        $payout = DB::transaction(function () use ($user, $account, $amount, $cooldownHours): PayoutRequest {
            /** @var PayoutRequest|null $latest */
            $latest = PayoutRequest::query()
                ->where('user_id', $user->id)
                ->latest('created_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($cooldownHours > 0 && $latest?->created_at !== null) {
                $nextRequestAt = $latest->created_at->copy()->addHours($cooldownHours);

                if ($nextRequestAt->isFuture()) {
                    throw ValidationException::withMessages([
                        'amount' => 'Withdrawal ka period '.$cooldownHours
                            .' ghante hai. Agla payout request '
                            .$nextRequestAt->timezone((string) config('earning.timezone'))->format('d M, h:i A')
                            .' ke baad bhej sakte hain.',
                    ]);
                }
            }

            $quote = $this->quote($amount);

            $payout = PayoutRequest::query()->create([
                'user_id' => $user->id,
                'payout_account_id' => $account->id,
                'provider' => $account->provider,
                'account_name' => $account->account_name,
                'account_number' => $account->account_number,
                'amount' => $quote['net'],
                'gross_amount' => $quote['gross'],
                'fee' => $quote['fee'],
                'status' => PayoutStatus::Pending,
            ]);

            $this->wallet->debit(
                $user,
                $quote['gross'],
                WalletTransactionType::PayoutRequest,
                'Payout request #'.$payout->id.' ('.$account->provider->label().')',
                $payout,
            );

            if ($quote['fee'] > 0) {
                $this->wallet->creditFirstActiveAdmin(
                    $quote['fee'],
                    WalletTransactionType::PayoutAdminFee,
                    'Withdrawal admin fee: payout #'.$payout->id,
                    $payout,
                );
            }

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
                $payout->grossAmount(),
                WalletTransactionType::PayoutRefund,
                'Payout #'.$payout->id.' rejected — amount refunded',
                $payout,
            );

            /** @var WalletTransaction|null $adminFee */
            $adminFee = WalletTransaction::query()
                ->where('reference_type', $payout->getMorphClass())
                ->where('reference_id', $payout->id)
                ->where('type', WalletTransactionType::PayoutAdminFee)
                ->first();

            if ($adminFee !== null && $adminFee->user !== null && $payout->feeAmount() > 0) {
                $this->wallet->debit(
                    $adminFee->user,
                    $payout->feeAmount(),
                    WalletTransactionType::PayoutFeeReversal,
                    'Payout #'.$payout->id.' rejected — admin fee reversed',
                    $payout,
                );
            }

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
