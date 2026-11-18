<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Models\Deposit;
use App\Models\User;
use App\Notifications\DepositAdminNotification;
use App\Notifications\DepositCompletedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class DepositService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly CashmaalGateway $gateway,
    ) {}

    /**
     * @return array{amount: float, fee: float, total: float}
     */
    public function quote(float $amount): array
    {
        $net = round($amount, 2);
        $percent = max(0.0, (float) config('earning.deposit.admin_fee_percent', 10));
        $fee = round($net * $percent / 100, 2);

        return [
            'amount' => $net,
            'fee' => $fee,
            'total' => round($net + $fee, 2),
        ];
    }

    public function create(User $user, float $amount): Deposit
    {
        $minimum = (float) config('earning.deposit.min', 100);
        $maximum = (float) config('earning.deposit.max', 100000);

        if ($amount < $minimum || $amount > $maximum) {
            throw ValidationException::withMessages([
                'amount' => 'Deposit must be between '.number_format($minimum, 0).' and '.number_format($maximum, 0).' PKR.',
            ]);
        }

        if (! $this->gateway->isConfigured()) {
            throw ValidationException::withMessages([
                'amount' => 'Cashmaal deposits are not available right now. Contact support.',
            ]);
        }

        $quote = $this->quote($amount);

        return Deposit::query()->create([
            'user_id' => $user->id,
            'provider' => 'cashmaal',
            'order_id' => $this->uniqueOrderId(),
            'amount' => $quote['total'],
            'fee' => $quote['fee'],
            'credit_amount' => $quote['amount'],
            'currency' => $this->gateway->currency(),
            'status' => DepositStatus::Pending,
            'client_email' => $user->email,
        ]);
    }

    /**
     * Cashmaal expects the literal string **OK** so it stops retrying.
     */
    public function handleIpn(array $payload): string
    {
        $receivedKey = (string) ($payload['ipn_key'] ?? '');
        $expectedKey = $this->gateway->ipnKey();

        if (blank($expectedKey) || ! hash_equals($expectedKey, $receivedKey)) {
            return 'Invalid IPN key';
        }

        $webId = trim((string) ($payload['web_id'] ?? ''));

        if ($webId !== '' && ! hash_equals($this->gateway->webId(), $webId)) {
            return 'Invalid Web ID';
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $deposit = Deposit::query()->where('order_id', $orderId)->first();

        if (! $deposit) {
            return '**OK**';
        }

        $status = (string) ($payload['status'] ?? '');

        if ($status === '1') {
            $cmTid = trim((string) ($payload['CM_TID'] ?? ''));

            if ($cmTid === '') {
                return 'Missing transaction id';
            }

            // IPN alone is never enough. Cashmaal's verify API is the source
            // of truth, so an unpaid or replayed notification cannot credit
            // the wallet.
            $verified = $this->verifyCallback($deposit, $cmTid, $payload);
            $fresh = $deposit->fresh();

            if ($verified || ! $fresh?->isPending()) {
                return '**OK**';
            }

            return 'Payment verification pending';
        }

        if ($status === '2') {
            return 'Payment pending';
        }

        if ($status === '3') {
            $this->markFailed($deposit, 'The Cashmaal payment was rejected.', $payload);
        } elseif ($status === '0') {
            $this->markCancelled($deposit, $payload);
        }

        return '**OK**';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function complete(
        Deposit $deposit,
        ?string $cmTid = null,
        array $payload = [],
        float $fee = 0,
        ?float $creditAmount = null,
    ): Deposit {
        return DB::transaction(function () use ($deposit, $cmTid, $payload, $fee, $creditAmount): Deposit {
            /** @var Deposit $locked */
            $locked = Deposit::query()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCompleted()) {
                return $locked;
            }

            if (! $locked->isPending()) {
                return $locked;
            }

            $credit = $creditAmount ?? (float) $locked->credit_amount;
            $adminFee = round((float) $locked->fee, 2);

            $locked->forceFill([
                'status' => DepositStatus::Completed,
                'cm_tid' => filled($cmTid) ? $cmTid : $locked->cm_tid,
                'fee' => $adminFee,
                'credit_amount' => round($credit, 2),
                'payload' => array_merge(
                    $locked->payload ?? [],
                    $fee > 0 ? ['gateway_fee' => round($fee, 2)] : [],
                    $payload,
                ),
                'paid_at' => now(),
            ])->save();

            $this->wallet->credit(
                $locked->user,
                $credit,
                WalletTransactionType::Deposit,
                'Cashmaal deposit '.$locked->order_id,
                $locked,
            );

            if ($adminFee > 0) {
                $this->wallet->creditFirstActiveAdmin(
                    $adminFee,
                    WalletTransactionType::DepositAdminFee,
                    'Deposit admin fee: '.$locked->order_id,
                    $locked,
                );
            }

            $locked->user?->notify(new DepositCompletedNotification($locked));
            $this->notifyAdmins(new DepositAdminNotification($locked));

            return $locked->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function verifyCallback(Deposit $deposit, ?string $cmTid, array $context = []): bool
    {
        if (blank($cmTid)) {
            return false;
        }

        $data = $this->gateway->verify($cmTid);

        if (! is_array($data) || (string) ($data['status'] ?? '') !== '1') {
            return false;
        }

        $orderId = (string) ($data['order_id'] ?? '');

        if (filled($orderId) && $orderId !== $deposit->order_id) {
            return false;
        }

        $paidAmount = $this->paidAmount($data);

        if ($paidAmount + 0.01 < (float) $deposit->amount) {
            $this->markFailed(
                $deposit,
                'Cashmaal sent an insufficient amount: '.$paidAmount.' (expected '.$deposit->amount.')',
                array_merge($context, $data),
            );

            return false;
        }

        $this->complete(
            $deposit,
            $cmTid,
            array_merge($context, $data),
            (float) ($data['fee_in_PKR'] ?? 0),
        );

        return true;
    }

    /**
     * Cashmaal reports the net amount received and the gateway fee separately.
     * For PKR deposits, `PKR_amount_with_fee` is the total the user paid, so we
     * compare that with the amount requested in the redirect form.
     *
     * @param  array<string, mixed>  $data
     */
    private function paidAmount(array $data): float
    {
        $base = (float) ($data['PKR_amount'] ?? $data['Amount'] ?? 0);
        $fee = (float) ($data['fee_in_PKR'] ?? $data['fee'] ?? 0);
        $withFee = (float) ($data['PKR_amount_with_fee'] ?? 0);

        return round(max($base + $fee, $withFee, $base), 2);
    }

    public function findByOrderId(string $orderId): ?Deposit
    {
        return Deposit::query()->where('order_id', $orderId)->first();
    }

    public function findByCmTid(string $cmTid): ?Deposit
    {
        return Deposit::query()->where('cm_tid', $cmTid)->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function markFailed(Deposit $deposit, string $notes, array $payload = []): Deposit
    {
        return $this->close($deposit, DepositStatus::Failed, $notes, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function markCancelled(Deposit $deposit, array $payload = []): Deposit
    {
        return $this->close($deposit, DepositStatus::Cancelled, 'The Cashmaal payment was cancelled.', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function close(Deposit $deposit, DepositStatus $status, ?string $notes, array $payload): Deposit
    {
        return DB::transaction(function () use ($deposit, $status, $notes, $payload): Deposit {
            /** @var Deposit $locked */
            $locked = Deposit::query()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                return $locked;
            }

            $locked->forceFill([
                'status' => $status,
                'admin_notes' => $notes,
                'payload' => array_merge($locked->payload ?? [], $payload),
            ])->save();

            return $locked->refresh();
        });
    }

    private function uniqueOrderId(): string
    {
        do {
            $orderId = 'DEP'.now()->format('ymdHis').Str::upper(Str::random(6));
        } while (Deposit::query()->where('order_id', $orderId)->exists());

        return $orderId;
    }

    private function notifyAdmins(object $notification): void
    {
        $admins = User::query()
            ->where('role', UserRole::SuperAdmin->value)
            ->where('is_active', true)
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        try {
            Notification::send($admins, $notification);
        } catch (Throwable) {
            // A deposit must still complete if notifications fail.
        }
    }
}
